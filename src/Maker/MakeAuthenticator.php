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

use Doctrine\ORM\EntityManagerInterface;
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
use Symfony\Bundle\MakerBundle\Util\TemplateComponentGenerator;
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
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

    private $fileManager;

    private $configUpdater;

    private $generator;

    private $doctrineHelper;

    private $useSecurity52 = false;

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
            $input->hasArgument('username-field') ? $input->getArgument('username-field') : null,
            $generator
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

    private function generateAuthenticatorClass(array $securityData, string $authenticatorType, string $authenticatorClass, $userClass, $userNameField, Generator $generator): void
    {
        // generate authenticator class
        if (self::AUTH_TYPE_EMPTY_AUTHENTICATOR === $authenticatorType) {
            $this->generator->generateClass(
                $authenticatorClass,
                sprintf('authenticator/%sEmptyAuthenticator.tpl.php', $this->useSecurity52 ? 'Security52' : ''),
                ['provider_key_type_hint' => $this->providerKeyTypeHint()]
            );

            return;
        }

        $userClassNameDetails = $this->generator->createClassNameDetails(
            '\\'.$userClass,
            'Entity\\'
        );

        $useStatements = [
            RedirectResponse::class,
            Request::class,
            UrlGeneratorInterface::class,
            TokenInterface::class,
            Security::class,
            TargetPathTrait::class,
        ];

        $guardUseStatements = [
            InvalidCsrfTokenException::class,
            UsernameNotFoundException::class,
            UserInterface::class,
            UserProviderInterface::class,
            CsrfToken::class,
            CsrfTokenManagerInterface::class,
            AbstractFormLoginAuthenticator::class,
        ];

        $security53UseStatements = [
            Response::class,
            AbstractLoginFormAuthenticator::class,
            CsrfTokenBadge::class,
            UserBadge::class,
            PasswordCredentials::class,
            Passport::class,
            PassportInterface::class,
        ];

        $useStatements = array_merge($useStatements, ($this->useSecurity52 ? $security53UseStatements : $guardUseStatements));

        $isEntity = $this->doctrineHelper->isClassAMappedEntity($userClass);
        $hasEncoder = $this->userClassHasEncoder($securityData, $userClass);
        $guardPasswordAuthenticated = $hasEncoder && interface_exists(
                PasswordAuthenticatedInterface::class);

        $guardTemplateVariables = [
            'user_class_name' => $userClassNameDetails->getShortName(),
            'user_needs_encoder' => $hasEncoder,
            'password_class_details' => $generator->createClassNameDetails(UserPasswordEncoderInterface::class, '\\'),
            'password_variable_name' => 'passwordEncoder',
            'user_is_entity' => $isEntity,
            'provider_key_type_hint' => $this->providerKeyTypeHint(),
            'password_authenticated' => $guardPasswordAuthenticated,
        ];

        $security53TemplateVariables = [
            'username_field_var' => Str::asLowerCamelCase($userNameField),
        ];

        if (!$this->useSecurity52) {
            if ($isEntity) {
                $useStatements[] = $userClassNameDetails->getFullName();
                $useStatements[] = EntityManagerInterface::class;
            }

            if ($hasEncoder) {
                $encoder = UserPasswordEncoderInterface::class;

                if (interface_exists(UserPasswordHasherInterface::class)) {
                    $encoder = UserPasswordHasherInterface::class;
                    $guardTemplateVariables['password_class_details'] = $generator->createClassNameDetails(UserPasswordHasherInterface::class, '\\');
                    $guardTemplateVariables['password_variable_name'] = 'passwordHasher';
                }

                $useStatements[] = $encoder;
            }

            if ($guardPasswordAuthenticated) {
                $useStatements[] = PasswordAuthenticatedInterface::class;
            }
        }

        $this->generator->generateClass(
            $authenticatorClass,
            sprintf('authenticator/%sLoginFormAuthenticator.tpl.php', $this->useSecurity52 ? 'Security52' : ''),
            array_merge([
                'use_statements' => TemplateComponentGenerator::generateUseStatements($useStatements),
                'username_field' => $userNameField,
                'username_field_label' => Str::asHumanWords($userNameField),
            ], ($this->useSecurity52 ? $security53TemplateVariables : $guardTemplateVariables)
            )
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

        $securityControllerBuilder = new SecurityControllerBuilder();
        $securityControllerBuilder->addLoginMethod($manipulator);
        if ($logoutSetup) {
            $securityControllerBuilder->addLogoutMethod($manipulator);
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

            if (!$this->userClassHasEncoder($securityData, $userClass)) {
                $nextTexts[] = sprintf('- Check the user\'s password in <info>%s::checkCredentials()</info>.', $authenticatorClass);
            }

            $nextTexts[] = '- Review & adapt the login template: <info>'.$this->fileManager->getPathForTemplate('security/login.html.twig').'</info>.';
        }

        return $nextTexts;
    }

    private function userClassHasEncoder(array $securityData, string $userClass): bool
    {
        $userNeedsEncoder = false;
        if (isset($securityData['security']['encoders']) && $securityData['security']['encoders']) {
            foreach ($securityData['security']['encoders'] as $userClassWithEncoder => $encoder) {
                if ($userClass === $userClassWithEncoder || is_subclass_of($userClass, $userClassWithEncoder)) {
                    $userNeedsEncoder = true;
                }
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

    private function providerKeyTypeHint(): string
    {
        $reflectionMethod = new \ReflectionMethod(AbstractFormLoginAuthenticator::class, 'onAuthenticationSuccess');
        $type = $reflectionMethod->getParameters()[2]->getType();

        if (!$type instanceof \ReflectionNamedType) {
            return '';
        }

        return sprintf('%s ', $type->getName());
    }
}
