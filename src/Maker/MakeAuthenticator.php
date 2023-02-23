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
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Util\YamlManipulationFailedException;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security as LegacySecurity;
use Symfony\Component\Security\Guard\AuthenticatorInterface as GuardAuthenticatorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
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

    public function __construct(
        private FileManager $fileManager,
        private SecurityConfigUpdater $configUpdater,
        private Generator $generator,
        private DoctrineHelper $doctrineHelper,
        private SecurityControllerBuilder $securityControllerBuilder,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:auth';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a Guard authenticator of different flavors';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeAuth.txt'));
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (!$this->fileManager->fileExists($path = 'config/packages/security.yaml')) {
            throw new RuntimeCommandException('The file "config/packages/security.yaml" does not exist. PHP & XML configuration formats are currently not supported.');
        }
        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($path));
        $securityData = $manipulator->getData();

        // @legacy - Can be removed when Symfony 5.4 support is dropped
        if (interface_exists(GuardAuthenticatorInterface::class) && !($securityData['security']['enable_authenticator_manager'] ?? false)) {
            throw new RuntimeCommandException('MakerBundle only supports the new authenticator based security system. See https://symfony.com/doc/current/security.html');
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
            $missingPackagesMessage = $this->addDependencies($neededDependencies, 'Twig must be installed to display the login form.');

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

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
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

        if (self::AUTH_TYPE_FORM_LOGIN !== $input->getArgument('authenticator-type')) {
            $entryPoint = false;
        }

        try {
            $newYaml = $this->configUpdater->updateForAuthenticator(
                $this->fileManager->getFileContents($path = 'config/packages/security.yaml'),
                $input->getOption('firewall-name'),
                $entryPoint,
                $input->getArgument('authenticator-class'),
                $input->hasArgument('logout-setup') ? $input->getArgument('logout-setup') : false
            );
            $generator->dumpFile($path, $newYaml);
            $securityYamlUpdated = true;
        } catch (YamlManipulationFailedException) {
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

    private function generateAuthenticatorClass(array $securityData, string $authenticatorType, string $authenticatorClass, $userClass, $userNameField): void
    {
        $useStatements = new UseStatementGenerator([
            Request::class,
            Response::class,
            TokenInterface::class,
            Passport::class,
        ]);

        // generate authenticator class
        if (self::AUTH_TYPE_EMPTY_AUTHENTICATOR === $authenticatorType) {
            $useStatements->addUseStatement([
                AuthenticationException::class,
                AbstractAuthenticator::class,
            ]);

            $this->generator->generateClass(
                $authenticatorClass,
                'authenticator/EmptyAuthenticator.tpl.php',
                ['use_statements' => $useStatements]
            );

            return;
        }

        $useStatements->addUseStatement([
            RedirectResponse::class,
            UrlGeneratorInterface::class,
            AbstractLoginFormAuthenticator::class,
            CsrfTokenBadge::class,
            UserBadge::class,
            PasswordCredentials::class,
            TargetPathTrait::class,
        ]);

        // @legacy - Can be removed when Symfony 5.4 support is dropped
        if (class_exists(Security::class)) {
            $useStatements->addUseStatement(Security::class);
        } else {
            $useStatements->addUseStatement(LegacySecurity::class);
        }

        $userClassNameDetails = $this->generator->createClassNameDetails(
            '\\'.$userClass,
            'Entity\\'
        );

        $this->generator->generateClass(
            $authenticatorClass,
            'authenticator/LoginFormAuthenticator.tpl.php',
            [
                'use_statements' => $useStatements,
                'user_fully_qualified_class_name' => trim($userClassNameDetails->getFullName(), '\\'),
                'user_class_name' => $userClassNameDetails->getShortName(),
                'username_field' => $userNameField,
                'username_field_label' => Str::asHumanWords($userNameField),
                'username_field_var' => Str::asLowerCamelCase($userNameField),
                'user_needs_encoder' => $this->userClassHasEncoder($securityData, $userClass),
                'user_is_entity' => $this->doctrineHelper->isClassAMappedEntity($userClass),
            ]
        );
    }

    private function generateFormLoginFiles(string $controllerClass, string $userNameField, bool $logoutSetup): void
    {
        $controllerClassNameDetails = $this->generator->createClassNameDetails(
            $controllerClass,
            'Controller\\',
            'Controller'
        );

        if (!class_exists($controllerClassNameDetails->getFullName())) {
            $useStatements = new UseStatementGenerator([
                AbstractController::class,
                Route::class,
                AuthenticationUtils::class,
            ]);

            $controllerPath = $this->generator->generateController(
                $controllerClassNameDetails->getFullName(),
                'authenticator/EmptySecurityController.tpl.php',
                ['use_statements' => $useStatements]
            );

            $controllerSourceCode = $this->generator->getFileContentsForPendingOperation($controllerPath);
        } else {
            $controllerPath = $this->fileManager->getRelativePathForFutureClass($controllerClassNameDetails->getFullName());
            $controllerSourceCode = $this->fileManager->getFileContents($controllerPath);
        }

        if (method_exists($controllerClassNameDetails->getFullName(), 'login')) {
            throw new RuntimeCommandException(sprintf('Method "login" already exists on class %s', $controllerClassNameDetails->getFullName()));
        }

        $manipulator = new ClassSourceManipulator(
            sourceCode: $controllerSourceCode,
            overwrite: true
        );

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
                $logoutSetup
            );
            $nextTexts[] = "- Your <info>security.yaml</info> could not be updated automatically. You'll need to add the following config manually:\n\n".$yamlExample;
        }

        if (self::AUTH_TYPE_FORM_LOGIN === $authenticatorType) {
            $nextTexts[] = sprintf('- Finish the redirect "TODO" in the <info>%s::onAuthenticationSuccess()</info> method.', $authenticatorClass);

            if (!$this->doctrineHelper->isClassAMappedEntity($userClass)) {
                $nextTexts[] = sprintf('- Review <info>%s::getUser()</info> to make sure it matches your needs.', $authenticatorClass);
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

    public function configureDependencies(DependencyBuilder $dependencies, InputInterface $input = null): void
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
