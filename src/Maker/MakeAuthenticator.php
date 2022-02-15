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
use Symfony\Bundle\MakerBundle\Str;
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
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Ryan Weaver   <ryan@symfonycasts.com>
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class MakeAuthenticator extends AbstractMaker
{
    private const AUTH_TYPE_EMPTY_AUTHENTICATOR = 'empty-authenticator';
    private const AUTH_TYPE_FORM_LOGIN = 'form-login';

    private $fileManager;

    private $configUpdater;

    private $generator;

    private $doctrineHelper;

    private $securityControllerBuilder;

    private $useSecurity52 = false;

    public function __construct(FileManager $fileManager, SecurityConfigUpdater $configUpdater, Generator $generator, DoctrineHelper $doctrineHelper, SecurityControllerBuilder $securityControllerBuilder)
    {
        $this->fileManager = $fileManager;
        $this->configUpdater = $configUpdater;
        $this->generator = $generator;
        $this->doctrineHelper = $doctrineHelper;
        $this->securityControllerBuilder = $securityControllerBuilder;
    }

    public static function getCommandName(): string
    {
        return 'make:auth';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a Guard authenticator of different flavors';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeAuth.txt'));
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (!$this->fileManager->fileExists($path = 'config/packages/security.yaml')) {
            throw new RuntimeCommandException('The file "config/packages/security.yaml" does not exist. This command requires that file to exist so that it can be updated.');
        }
        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($path));
        $securityData = $manipulator->getData();

        // Determine if we should use new security features introduced in Symfony 5.2
        if ($securityData['security']['enable_authenticator_manager'] ?? false) {
            $this->useSecurity52 = true;
        }

        if ($this->useSecurity52 && !class_exists(UserBadge::class)) {
            throw new RuntimeCommandException('MakerBundle does not support generating authenticators using the new authenticator system before symfony/security-bundle 5.2. Please upgrade to 5.2 and try again.');
        }

        // authenticator type
        $authenticatorTypeValues = [
            'Empty authenticator' => self::AUTH_TYPE_EMPTY_AUTHENTICATOR,
            'Login form authenticator' => self::AUTH_TYPE_FORM_LOGIN,
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
            $neededDependencies = [TwigBundle::class => 'twig'];
            $missingPackagesMessage = 'Twig must be installed to display the login form.';

            if (Kernel::VERSION_ID < 40100) {
                $neededDependencies[Form::class] = 'symfony/form';
                $missingPackagesMessage = 'Twig and symfony/form must be installed to display the login form';
            }

            $missingPackagesMessage = $this->addDependencies($neededDependencies, $missingPackagesMessage);
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
                    $this->generator->createClassNameDetails($answer, 'Security\\', 'Authenticator')->getFullName()
                );
            }
        );
        $input->setArgument('authenticator-class', $io->askQuestion($questionAuthenticatorClass));

        $interactiveSecurityHelper = new InteractiveSecurityHelper();
        $command->addOption('firewall-name', null, InputOption::VALUE_OPTIONAL);
        $input->setOption('firewall-name', $firewallName = $interactiveSecurityHelper->guessFirewallName($io, $securityData));

        $command->addOption('entry-point', null, InputOption::VALUE_OPTIONAL);

        if (!$this->useSecurity52) {
            $input->setOption(
                'entry-point',
                $interactiveSecurityHelper->guessEntryPoint($io, $securityData, $input->getArgument('authenticator-class'), $firewallName)
            );
        }

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

            $command->addArgument('user-class', InputArgument::REQUIRED);
            $input->setArgument(
                'user-class',
                $userClass = $interactiveSecurityHelper->guessUserClass($io, $securityData['security']['providers'])
            );

            $command->addArgument('username-field', InputArgument::REQUIRED);
            $input->setArgument(
                'username-field',
                $interactiveSecurityHelper->guessUserNameField($io, $userClass, $securityData['security']['providers'])
            );

            $command->addArgument('logout-setup', InputArgument::REQUIRED);
            $input->setArgument(
                'logout-setup',
                $io->confirm(
                    'Do you want to generate a \'/logout\' URL?',
                    true
                )
            );
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents('config/packages/security.yaml'));
        $securityData = $manipulator->getData();

        $this->generateAuthenticatorClass(
            $securityData,
            $input->getArgument('authenticator-type'),
            $input->getArgument('authenticator-class'),
            $input->hasArgument('user-class') ? $input->getArgument('user-class') : null,
            $input->hasArgument('username-field') ? $input->getArgument('username-field') : null
        );

        // update security.yaml with guard config
        $securityYamlUpdated = false;

        $entryPoint = $input->getOption('entry-point');

        if ($this->useSecurity52 && self::AUTH_TYPE_FORM_LOGIN !== $input->getArgument('authenticator-type')) {
            $entryPoint = false;
        }

        try {
            $newYaml = $this->configUpdater->updateForAuthenticator(
                $this->fileManager->getFileContents($path = 'config/packages/security.yaml'),
                $input->getOption('firewall-name'),
                $entryPoint,
                $input->getArgument('authenticator-class'),
                $input->hasArgument('logout-setup') ? $input->getArgument('logout-setup') : false,
                $this->useSecurity52
            );
            $generator->dumpFile($path, $newYaml);
            $securityYamlUpdated = true;
        } catch (YamlManipulationFailedException $e) {
        }

        if (self::AUTH_TYPE_FORM_LOGIN === $input->getArgument('authenticator-type')) {
            $this->generateFormLoginFiles(
                $input->getArgument('controller-class'),
                $input->getArgument('username-field'),
                $input->getArgument('logout-setup')
            );
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text(
            $this->generateNextMessage(
                $securityYamlUpdated,
                $input->getArgument('authenticator-type'),
                $input->getArgument('authenticator-class'),
                $securityData,
                $input->hasArgument('user-class') ? $input->getArgument('user-class') : null,
                $input->hasArgument('logout-setup') ? $input->getArgument('logout-setup') : false
            )
        );
    }

    private function generateAuthenticatorClass(array $securityData, string $authenticatorType, string $authenticatorClass, $userClass, $userNameField)
    {
        // generate authenticator class
        if (self::AUTH_TYPE_EMPTY_AUTHENTICATOR === $authenticatorType) {
            $this->generator->generateClass(
                $authenticatorClass,
                sprintf('authenticator/%sEmptyAuthenticator.tpl.php', $this->useSecurity52 ? 'Security52' : ''),
                [
                    'provider_key_type_hint' => $this->getGuardProviderKeyTypeHint(),
                    'use_legacy_passport_interface' => $this->shouldUseLegacyPassportInterface(),
                ]
            );

            return;
        }

        $userClassNameDetails = $this->generator->createClassNameDetails(
            '\\'.$userClass,
            'Entity\\'
        );

        $this->generator->generateClass(
            $authenticatorClass,
            sprintf('authenticator/%sLoginFormAuthenticator.tpl.php', $this->useSecurity52 ? 'Security52' : ''),
            [
                'user_fully_qualified_class_name' => trim($userClassNameDetails->getFullName(), '\\'),
                'user_class_name' => $userClassNameDetails->getShortName(),
                'username_field' => $userNameField,
                'username_field_label' => Str::asHumanWords($userNameField),
                'username_field_var' => Str::asLowerCamelCase($userNameField),
                'user_needs_encoder' => $this->userClassHasEncoder($securityData, $userClass),
                'user_is_entity' => $this->doctrineHelper->isClassAMappedEntity($userClass),
                'provider_key_type_hint' => $this->getGuardProviderKeyTypeHint(),
                'use_legacy_passport_interface' => $this->shouldUseLegacyPassportInterface(),
            ]
        );
    }

    private function generateFormLoginFiles(string $controllerClass, string $userNameField, bool $logoutSetup)
    {
        $controllerClassNameDetails = $this->generator->createClassNameDetails(
            $controllerClass,
            'Controller\\',
            'Controller'
        );

        if (!class_exists($controllerClassNameDetails->getFullName())) {
            $controllerPath = $this->generator->generateController(
                $controllerClassNameDetails->getFullName(),
                'authenticator/EmptySecurityController.tpl.php'
            );

            $controllerSourceCode = $this->generator->getFileContentsForPendingOperation($controllerPath);
        } else {
            $controllerPath = $this->fileManager->getRelativePathForFutureClass($controllerClassNameDetails->getFullName());
            $controllerSourceCode = $this->fileManager->getFileContents($controllerPath);
        }

        if (method_exists($controllerClassNameDetails->getFullName(), 'login')) {
            throw new RuntimeCommandException(sprintf('Method "login" already exists on class %s', $controllerClassNameDetails->getFullName()));
        }

        $manipulator = new ClassSourceManipulator($controllerSourceCode, true);

        $this->securityControllerBuilder->addLoginMethod($manipulator);

        if ($logoutSetup) {
            $this->securityControllerBuilder->addLogoutMethod($manipulator);
        }

        $this->generator->dumpFile($controllerPath, $manipulator->getSourceCode());

        // create login form template
        $this->generator->generateTemplate(
            'security/login.html.twig',
            'authenticator/login_form.tpl.php',
            [
                'username_field' => $userNameField,
                'username_is_email' => false !== stripos($userNameField, 'email'),
                'username_label' => ucfirst(Str::asHumanWords($userNameField)),
                'logout_setup' => $logoutSetup,
            ]
        );
    }

    private function generateNextMessage(bool $securityYamlUpdated, string $authenticatorType, string $authenticatorClass, array $securityData, $userClass, bool $logoutSetup): array
    {
        $nextTexts = ['Next:'];
        $nextTexts[] = '- Customize your new authenticator.';

        if (!$securityYamlUpdated) {
            $yamlExample = $this->configUpdater->updateForAuthenticator(
                'security: {}',
                'main',
                null,
                $authenticatorClass,
                $logoutSetup,
                $this->useSecurity52
            );
            $nextTexts[] = "- Your <info>security.yaml</info> could not be updated automatically. You'll need to add the following config manually:\n\n".$yamlExample;
        }

        if (self::AUTH_TYPE_FORM_LOGIN === $authenticatorType) {
            $nextTexts[] = sprintf('- Finish the redirect "TODO" in the <info>%s::onAuthenticationSuccess()</info> method.', $authenticatorClass);

            if (!$this->doctrineHelper->isClassAMappedEntity($userClass)) {
                $nextTexts[] = sprintf('- Review <info>%s::getUser()</info> to make sure it matches your needs.', $authenticatorClass);
            }

            // this only applies to Guard authentication AND if the user does not have a hasher configured
            if (!$this->useSecurity52 && !$this->userClassHasEncoder($securityData, $userClass)) {
                $nextTexts[] = sprintf('- Check the user\'s password in <info>%s::checkCredentials()</info>.', $authenticatorClass);
            }

            $nextTexts[] = '- Review & adapt the login template: <info>'.$this->fileManager->getPathForTemplate('security/login.html.twig').'</info>.';
        }

        return $nextTexts;
    }

    private function userClassHasEncoder(array $securityData, string $userClass): bool
    {
        $userNeedsEncoder = false;
        $hashersData = $securityData['security']['encoders'] ?? $securityData['security']['encoders'] ?? [];

        foreach ($hashersData as $userClassWithEncoder => $encoder) {
            if ($userClass === $userClassWithEncoder || is_subclass_of($userClass, $userClassWithEncoder) || class_implements($userClass, $userClassWithEncoder)) {
                $userNeedsEncoder = true;
            }
        }

        return $userNeedsEncoder;
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

    /**
     * Calculates the type-hint used for the $provider argument (string or nothing) for Guard.
     */
    private function getGuardProviderKeyTypeHint(): string
    {
        // doesn't matter: this only applies to non-Guard authenticators
        if (!class_exists(AbstractFormLoginAuthenticator::class)) {
            return '';
        }

        $reflectionMethod = new \ReflectionMethod(AbstractFormLoginAuthenticator::class, 'onAuthenticationSuccess');
        $type = $reflectionMethod->getParameters()[2]->getType();

        if (!$type instanceof \ReflectionNamedType) {
            return '';
        }

        return sprintf('%s ', $type->getName());
    }

    private function shouldUseLegacyPassportInterface(): bool
    {
        // only applies to new authenticator security
        if (!$this->useSecurity52) {
            return false;
        }

        // legacy: checking for Symfony 5.2 & 5.3 before PassportInterface deprecation
        $class = new \ReflectionClass(AuthenticatorInterface::class);
        $method = $class->getMethod('authenticate');

        // 5.4 where return type is temporarily removed
        if (!$method->getReturnType()) {
            return false;
        }

        return PassportInterface::class === $method->getReturnType()->getName();
    }
}
