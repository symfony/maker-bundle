<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Security\InteractiveSecurityHelper;
use Symfony\Bundle\MakerBundle\Security\SecurityConfigUpdater;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\YamlManipulationFailedException;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Ryan Weaver <ryan@knpuniversity.com>
 *
 * @internal
 */
final class MakeAuthenticator extends AbstractMaker
{
    const AUTH_TYPE_EMPTY_AUTHENTICATOR = 'empty-authenticator';
    const AUTH_TYPE_FORM_LOGIN = 'form-login';

    private $fileManager;

    private $configUpdater;

    private $generator;

    private $doctrineHelper;

    public function __construct(FileManager $fileManager, SecurityConfigUpdater $configUpdater, Generator $generator, DoctrineHelper $doctrineHelper)
    {
        $this->fileManager = $fileManager;
        $this->configUpdater = $configUpdater;
        $this->generator = $generator;
        $this->doctrineHelper = $doctrineHelper;
    }

    public static function getCommandName(): string
    {
        return 'make:auth';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates an empty Guard authenticator')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeAuth.txt'));
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        $authenticatorTypeValues = [
            'Empty authenticator' => self::AUTH_TYPE_EMPTY_AUTHENTICATOR,
            'Form login' => self::AUTH_TYPE_FORM_LOGIN,
        ];
        $command->addArgument('authenticator-type', InputArgument::REQUIRED);
        $authenticatorType = $io->choice(
            'Which authentication type do you want ?',
            array_keys($authenticatorTypeValues),
            key($authenticatorTypeValues)
        );
        $input->setArgument(
            'authenticator-type',
            $authenticatorTypeValues[$authenticatorType]
        );

        $command->addArgument('authenticator-class', InputArgument::REQUIRED);
        $input->setArgument(
            'authenticator-class',
            $io->ask('The class name of the authenticator to create (e.g. <fg=yellow>AppCustomAuthenticator</>)')
        );

        // TODO : validate class name

        if (null === $input->getArgument('authenticator-class')) {
            throw new RuntimeCommandException('The authenticator class could not be empty!');
        }

        if (!$this->fileManager->fileExists($path = 'config/packages/security.yaml')) {
            return;
        }

        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($path));
        $securityData = $manipulator->getData();

        $interactiveSecurityHelper = new InteractiveSecurityHelper();

        $command->addOption('firewall-name', null, InputOption::VALUE_OPTIONAL);
        $input->setOption('firewall-name', $firewallName = $interactiveSecurityHelper->guessFirewallName($io, $securityData));

        $command->addOption('entry-point', null, InputOption::VALUE_OPTIONAL);

        $authenticatorClassNameDetails = $this->generator->createClassNameDetails(
            $input->getArgument('authenticator-class'),
            'Security\\'
        );

        $input->setOption(
            'entry-point',
            $interactiveSecurityHelper->guessEntryPoint($io, $securityData, $authenticatorClassNameDetails->getFullName(), $firewallName)
        );

        if (self::AUTH_TYPE_FORM_LOGIN === $input->getArgument('authenticator-type')) {
            $command->addArgument('controller-class', InputArgument::OPTIONAL, 'Choose a name for the controller class (e.g. <fg=yellow>SecurityController</>)', 'SecurityController');

            $controllerClass = $io->ask(
                $command->getDefinition()->getArgument('controller-class')->getDescription(),
                $command->getDefinition()->getArgument('controller-class')->getDefault()
            );
            // TODO : validate class name
            $input->setArgument('controller-class', $controllerClass);

            if (!isset($securityData['security']['providers']) || !$securityData['security']['providers']) {
                throw new RuntimeCommandException('You need to have at least one provider defined in security.yaml');
            }

            $command->addArgument('user-class', InputArgument::OPTIONAL);
            if (1 === \count($securityData['security']['providers']) && isset(current($securityData['security']['providers'])['entity'])) {
                $entityProvider = current($securityData['security']['providers']);
                $userClass = $entityProvider['entity']['class'];
            } else {
                $userClass = $io->ask(
                    'Enter the User class you want to authenticate (e.g. <fg=yellow>App\\Entity\\User</>)
 (It has to be handled by one of the firewall\'s providers)',
                    class_exists('App\\Entity\\User') && isset(class_implements('App\\Entity\\User')[UserInterface::class]) ? 'App\\Entity\\User'
                        : class_exists('App\\Security\\User') && isset(class_implements('App\\Security\\User')[UserInterface::class]) ? 'App\\Security\\User' : null
                );

                if (!class_exists($userClass)) {
                    throw new RuntimeCommandException(sprintf('The class "%s" does not exist', $userClass));
                }

                if (!isset(class_implements($userClass)[UserInterface::class])) {
                    throw new RuntimeCommandException(sprintf('The class "%s" doesn\'t implement "%s"', $userClass, UserInterface::class));
                }
            }
            $input->setArgument('user-class', $userClass);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $classNameDetails = $generator->createClassNameDetails(
            $input->getArgument('authenticator-class'),
            'Security\\'
        );

        // TODO update LoginFormNotEntityAuthenticator

        $generator->generateClass(
            $classNameDetails->getFullName(),
            self::AUTH_TYPE_FORM_LOGIN === $input->getArgument('authenticator-type') ?
                ($this->doctrineHelper->isClassAMappedEntity($input->getArgument('user-class')) ? 'authenticator/LoginFormEntityAuthenticator.tpl.php' : 'authenticator/LoginFormNotEntityAuthenticator.tpl.php')
                : 'authenticator/Empty.tpl.php',
            []
        );

        $securityYamlUpdated = false;
        $path = 'config/packages/security.yaml';
        if ($this->fileManager->fileExists($path)) {
            try {
                $newYaml = $this->configUpdater->updateForAuthenticator(
                    $this->fileManager->getFileContents($path),
                    $input->getOption('firewall-name'),
                    $input->getOption('entry-point'),
                    $classNameDetails->getFullName()
                );
                $generator->dumpFile($path, $newYaml);
                $securityYamlUpdated = true;
            } catch (YamlManipulationFailedException $e) {
            }
        }

        if (self::AUTH_TYPE_FORM_LOGIN === $input->getArgument('authenticator-type')) {
            // TODO check if SecurityController exists
            $controllerClassNameDetails = $generator->createClassNameDetails(
                $input->getArgument('controller-class'),
                'Controller\\',
                'Controller'
            );

            $controllerPath = $generator->generateClass(
                $controllerClassNameDetails->getFullName(),
                'login_form/SecurityController.tpl.php',
                [
                    'parent_class_name' => \method_exists(AbstractController::class, 'getParameter') ? 'AbstractController' : 'Controller',
                ]
            );

            $templateName = Str::asFilePath($controllerClassNameDetails->getRelativeNameWithoutSuffix()).'/login.html.twig';
            $generator->generateFile(
                'templates/'.$templateName,
                'login_form/login_form.tpl.php',
                [
                    'controller_path' => $controllerPath,
                ]
            );
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $text = ['Next: Customize your new authenticator.'];
        if (!$securityYamlUpdated) {
            $yamlExample = $this->configUpdater->updateForAuthenticator(
                'security: {}',
                'main',
                null,
                $classNameDetails->getFullName()
            );
            $text[] = "Your <info>security.yaml</info> could not be updated automatically. You'll need to add the following config manually:\n\n".$yamlExample;
        }
        $io->text($text);
    }

    public function configureDependencies(DependencyBuilder $dependencies, InputInterface $input = null)
    {
        $dependencies->addClassDependency(
            SecurityBundle::class,
            'security'
        );

        // needed to update the YAML files
        $dependencies->addClassDependency(
            Yaml::class,
            'yaml'
        );
    }
}
