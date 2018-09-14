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
use Symfony\Bundle\MakerBundle\Security\SecurityControllerBuilder;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\YamlManipulationFailedException;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
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
        if (!$this->fileManager->fileExists($path = 'config/packages/security.yaml')) {
            throw new RuntimeCommandException('File "security.yaml" does not exist!');
        }
        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($path));
        $securityData = $manipulator->getData();

        // authenticator type
        $authenticatorTypeValues = [
            'Empty authenticator' => self::AUTH_TYPE_EMPTY_AUTHENTICATOR,
            'Form login' => self::AUTH_TYPE_FORM_LOGIN,
        ];
        $command->addArgument('authenticator-type', InputArgument::REQUIRED);
        $authenticatorType = $io->choice(
            'What style of authentication do you want?',
            array_keys($authenticatorTypeValues),
            key($authenticatorTypeValues)
        );
        $input->setArgument(
            'authenticator-type',
            $authenticatorTypeValues[$authenticatorType]
        );

        if (self::AUTH_TYPE_FORM_LOGIN === $input->getArgument('authenticator-type')) {
            $dependencies = new DependencyBuilder();
            $dependencies->addClassDependency(
                TwigBundle::class,
                'tiwg'
            );

            $missingPackagesMessage = $dependencies->getMissingPackagesMessage(self::getCommandName(), 'Twig must be installed to display login form');
            if ($missingPackagesMessage) {
                throw new RuntimeCommandException($missingPackagesMessage);
            }

            if (!isset($securityData['security']['providers']) || !$securityData['security']['providers']) {
                throw new RuntimeCommandException('To generate a form login authentication, you must configure at least one entry under "providers" in "security.yaml".');
            }
        }

        // authenticator class
        $command->addArgument('authenticator-class', InputArgument::REQUIRED);
        $questionAuthenticatorClass = new Question('The class name of the authenticator to create (e.g. <fg=yellow>AppCustomAuthenticator</>)');
        $questionAuthenticatorClass->setValidator(
            function ($answer) {
                Validator::notBlank($answer);

                return Validator::classDoesNotExist(
                    $this->generator->createClassNameDetails(
                        $answer,
                        'Security\\'
                    )->getFullName()
                );
            }
        );
        $input->setArgument('authenticator-class', $io->askQuestion($questionAuthenticatorClass));

        $interactiveSecurityHelper = new InteractiveSecurityHelper();
        $command->addOption('firewall-name', null, InputOption::VALUE_OPTIONAL);
        $input->setOption('firewall-name', $firewallName = $interactiveSecurityHelper->guessFirewallName($io, $securityData));

        $command->addOption('entry-point', null, InputOption::VALUE_OPTIONAL);
        $input->setOption(
            'entry-point',
            $interactiveSecurityHelper->guessEntryPoint($io, $securityData, $input->getArgument('authenticator-class'), $firewallName)
        );

        if (self::AUTH_TYPE_FORM_LOGIN === $input->getArgument('authenticator-type')) {
            $command->addArgument('controller-class', InputArgument::REQUIRED);
            $input->setArgument(
                'controller-class',
                $io->ask(
                    'Choose a name for the controller class (e.g. <fg=yellow>SecurityController</>)',
                    'SecurityController',
                    [Validator::class, 'validateClassName']
                )
            );

            $command->addArgument('user-class', InputArgument::OPTIONAL);
            $userClass = $interactiveSecurityHelper->guessUserClass($io, $securityData['security']['providers']);
            if (0 !== strpos($userClass, '\\')) {
                $userClass = '\\'.$userClass;
            }
            $input->setArgument('user-class', $userClass);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        // generate authenticator class
        if (self::AUTH_TYPE_EMPTY_AUTHENTICATOR === $input->getArgument('authenticator-type')) {
            $generator->generateClass(
                $input->getArgument('authenticator-class'),
                'authenticator/EmptyAuthenticator.tpl.php',
                []
            );
        } elseif ($this->doctrineHelper->isClassAMappedEntity($input->getArgument('user-class'))) {
            $userClassNameDetails = $generator->createClassNameDetails(
                $input->getArgument('user-class'),
                'Entity\\'
            );

            $generator->generateClass(
                $input->getArgument('authenticator-class'),
                'authenticator/LoginFormEntityAuthenticator.tpl.php',
                [
                    'user_fully_qualified_class_name' => trim($userClassNameDetails->getFullName(), '\\'),
                    'user_class_name' => $userClassNameDetails->getShortName(),
                ]
            );
        } else {
            $generator->generateClass(
                $input->getArgument('authenticator-class'),
                'authenticator/LoginFormNotEntityAuthenticator.tpl.php',
                []
            );
        }

        // update security.yaml with guard config
        $securityYamlUpdated = false;
        $path = 'config/packages/security.yaml';
        if ($this->fileManager->fileExists($path)) {
            try {
                $newYaml = $this->configUpdater->updateForAuthenticator(
                    $this->fileManager->getFileContents($path),
                    $input->getOption('firewall-name'),
                    $input->getOption('entry-point'),
                    $input->getArgument('authenticator-class')
                );
                $generator->dumpFile($path, $newYaml);
                $securityYamlUpdated = true;
            } catch (YamlManipulationFailedException $e) {
            }
        }

        if (self::AUTH_TYPE_FORM_LOGIN === $input->getArgument('authenticator-type')) {
            $this->generateFormLoginFiles($input);
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $text = ['Next: Customize your new authenticator.'];
        if (!$securityYamlUpdated) {
            $yamlExample = $this->configUpdater->updateForAuthenticator(
                'security: {}',
                'main',
                null,
                $input->getArgument('authenticator-class')
            );
            $text[] = "Your <info>security.yaml</info> could not be updated automatically. You'll need to add the following config manually:\n\n".$yamlExample;
        }
        $io->text($text);
    }

    private function generateFormLoginFiles(InputInterface $input)
    {
        $controllerClassNameDetails = $this->generator->createClassNameDetails(
            $input->getArgument('controller-class'),
            'Controller\\',
            'Controller'
        );

        if (!class_exists($controllerClassNameDetails->getFullName())) {
            $controllerPath = $this->generator->generateClass(
                $controllerClassNameDetails->getFullName(),
                'authenticator/EmptySecurityController.tpl.php',
                [
                    'parent_class_name' => \method_exists(AbstractController::class, 'getParameter') ? 'AbstractController' : 'Controller',
                ]
            );

            $controllerSourceCode = $this->generator->getFileContents($controllerPath);
        } else {
            $controllerPath = $this->fileManager->getRelativePathForFutureClass($controllerClassNameDetails->getFullName());
            $controllerSourceCode = $this->fileManager->getFileContents($controllerPath);
        }

        if (method_exists($controllerClassNameDetails->getFullName(), 'login')) {
            throw new RuntimeCommandException(sprintf('Method "login" already exists on class %s', $controllerClassNameDetails->getFullName()));
        }

        $manipulator = new ClassSourceManipulator($controllerSourceCode, true);

        $securityControllerBuilder = new SecurityControllerBuilder();
        $securityControllerBuilder->addLoginMethod($manipulator);

        $this->generator->dumpFile($controllerPath, $manipulator->getSourceCode());

        // create login form template
        $this->generator->generateFile(
            'templates/security/login.html.twig',
            'authenticator/login_form.tpl.php',
            [
                'controller_path' => $controllerPath,
            ]
        );
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
