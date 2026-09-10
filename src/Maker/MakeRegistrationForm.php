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

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Column;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\Common\CanGenerateTestsTrait;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Security\InteractiveSecurityHelper;
use Symfony\Bundle\MakerBundle\Security\Model\Authenticator;
use Symfony\Bundle\MakerBundle\Security\Model\AuthenticatorType;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassDetails;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\CliOutputHelper;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\SymfonyCastsVerifyEmailBundle;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelper;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * @author Ryan Weaver   <ryan@symfonycasts.com>
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class MakeRegistrationForm extends AbstractMaker
{
    use CanGenerateTestsTrait;

    public function __construct(
        private FileManager $fileManager,
        private FormTypeRenderer $formTypeRenderer,
        private DoctrineHelper $doctrineHelper,
        private ?RouterInterface $router = null,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:registration-form';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new registration form system';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setHelp($this->getHelpFileContents('MakeRegistrationForm.txt'))
        ;

        $command
            ->addOption('user-class', null, InputOption::VALUE_REQUIRED, 'The User class to create during registration (e.g. <fg=yellow>App\\Entity\\User</>)')
            ->addOption('username-field', null, InputOption::VALUE_REQUIRED, 'The property people enter when logging in (e.g. <fg=yellow>email</>)')
            ->addOption('password-field', null, InputOption::VALUE_REQUIRED, 'The property holding the hashed password (e.g. <fg=yellow>password</>)')
            ->addOption('unique-entity', null, InputOption::VALUE_NONE, 'Add a <fg=yellow>#[UniqueEntity]</> attribute to the User class')
            ->addOption('verify-email', null, InputOption::VALUE_NONE, 'Send an email to verify the address after registration')
            ->addOption('verify-anonymously', null, InputOption::VALUE_NONE, 'Embed the user id in the verification link, allowing verification without logging in')
            ->addOption('id-getter', null, InputOption::VALUE_REQUIRED, 'The method returning the user identifier (e.g. <fg=yellow>getId</>)')
            ->addOption('email-getter', null, InputOption::VALUE_REQUIRED, 'The method returning the email address (e.g. <fg=yellow>getEmail</>)')
            ->addOption('from-email-address', null, InputOption::VALUE_REQUIRED, 'The address confirmations are sent from (e.g. <fg=yellow>mailer@your-domain.com</>)')
            ->addOption('from-email-name', null, InputOption::VALUE_REQUIRED, 'The name associated with that address (e.g. <fg=yellow>Acme Mail Bot</>)')
            ->addOption('auto-login', null, InputOption::VALUE_NONE, 'Authenticate the user right after registering')
            ->addOption('authenticator', null, InputOption::VALUE_REQUIRED, 'The authenticator to log the user in with, when more than one is configured')
            ->addOption('redirect-route', null, InputOption::VALUE_REQUIRED, 'The route to redirect to after registration, when the user is not logged in automatically')
        ;

        $this->configureCommandWithTestsOption($command);
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $interactiveSecurityHelper = new InteractiveSecurityHelper();

        $securityData = $this->readSecurityData();
        $providersData = $securityData['security']['providers'] ?? [];

        if (!$input->getOption('user-class')) {
            $input->setOption('user-class', $interactiveSecurityHelper->guessUserClass(
                $io,
                $providersData,
                'Enter the User class that you want to create during registration (e.g. <fg=yellow>App\\Entity\\User</>)'
            ));
        }

        $userClass = $input->getOption('user-class');
        $io->text(\sprintf('Creating a registration form for <info>%s</info>', $userClass));

        if (!$input->getOption('username-field')) {
            $input->setOption('username-field', $interactiveSecurityHelper->guessUserNameField($io, $userClass, $providersData));
        }

        if (!$input->getOption('password-field')) {
            $input->setOption('password-field', $interactiveSecurityHelper->guessPasswordField($io, $userClass));
        }

        // see if it makes sense to add the UniqueEntity constraint
        $userClassDetails = new ClassDetails($userClass);

        if (!$input->getOption('unique-entity') && !$userClassDetails->hasAttribute(UniqueEntity::class)) {
            $input->setOption('unique-entity', (bool) $io->confirm(\sprintf('Do you want to add a <comment>#[UniqueEntity]</comment> validation attribute to your <comment>%s</comment> class to make sure duplicate accounts aren\'t created?', Str::getShortClassName($userClass))));
        }

        if (!$input->getOption('verify-email')) {
            $input->setOption('verify-email', (bool) $io->confirm('Do you want to send an email to verify the user\'s email address after registration?'));
        }

        if ($input->getOption('verify-email')) {
            $this->checkComponentsExist($io);

            $emailText[] = 'By default, users are required to be authenticated when they click the verification link that is emailed to them.';
            $emailText[] = 'This prevents the user from registering on their laptop, then clicking the link on their phone, without';
            $emailText[] = 'having to log in. To allow multi device email verification, we can embed a user id in the verification link.';
            $io->text($emailText);
            $io->newLine();

            if (!$input->getOption('verify-anonymously')) {
                $input->setOption('verify-anonymously', (bool) $io->confirm('Would you like to include the user id in the verification link to allow anonymous email verification?', false));
            }

            if (!$input->getOption('id-getter')) {
                $input->setOption('id-getter', $interactiveSecurityHelper->guessIdGetter($io, $userClass));
            }

            if (!$input->getOption('email-getter')) {
                $input->setOption('email-getter', $interactiveSecurityHelper->guessEmailGetter($io, $userClass, 'email'));
            }

            if (!$input->getOption('from-email-address')) {
                $input->setOption('from-email-address', $io->ask(
                    'What email address will be used to send registration confirmations? (e.g. <fg=yellow>mailer@your-domain.com</>)',
                    null,
                    Validator::validateEmailAddress(...)
                ));
            }

            if (!$input->getOption('from-email-name')) {
                $input->setOption('from-email-name', $io->ask(
                    'What "name" should be associated with that email address? (e.g. <fg=yellow>Acme Mail Bot</>)',
                    null,
                    Validator::notBlank(...)
                ));
            }
        }

        if (!$input->getOption('auto-login') && !$input->getOption('authenticator')) {
            $input->setOption('auto-login', (bool) $io->confirm('Do you want to automatically authenticate the user after registration?'));
        }

        if ($input->getOption('auto-login') || $input->getOption('authenticator')) {
            $this->interactAuthenticatorQuestions($input, $io, $interactiveSecurityHelper, $securityData);
        }

        if (!$input->getOption('authenticator') && !$input->getOption('redirect-route')) {
            $routeNames = array_keys($this->router->getRouteCollection()->all());
            $input->setOption('redirect-route', $io->choice('What route should the user be redirected to after registration?', $routeNames));
        }

        $this->interactSetGenerateTests($input, $io);
    }

    /**
     * @return array<string, mixed>
     */
    private function readSecurityData(): array
    {
        if (null === $this->router) {
            throw new RuntimeCommandException('Router have been explicitly disabled in your configuration. This command needs to use the router.');
        }

        if (!$this->fileManager->fileExists($path = 'config/packages/security.yaml')) {
            throw new RuntimeCommandException('The file "config/packages/security.yaml" does not exist. PHP & XML configuration formats are currently not supported.');
        }

        return (new YamlSourceManipulator($this->fileManager->getFileContents($path)))->getData();
    }

    /** @param array<string, mixed> $securityData */
    private function resolveAuthenticator(InputInterface $input, ConsoleStyle $io, InteractiveSecurityHelper $securityHelper, array $securityData): ?Authenticator
    {
        $authenticators = $securityHelper->getAuthenticatorsFromConfig($securityData['security']['firewalls'] ?? []);

        if ($name = $input->getOption('authenticator')) {
            foreach ($authenticators as $authenticator) {
                if ($name === (string) $authenticator) {
                    return $authenticator;
                }
            }

            throw new RuntimeCommandException(\sprintf('No authenticator named "%s" is configured. Available: "%s".', $name, implode('", "', $authenticators)));
        }

        if (!$input->getOption('auto-login')) {
            return null;
        }

        if (!$authenticators) {
            $io->note('No authenticators found - so your user won\'t be automatically authenticated after registering.');

            return null;
        }

        if (1 === \count($authenticators)) {
            return $authenticators[0];
        }

        throw new RuntimeCommandException(\sprintf('Multiple authenticators are configured, pass one with "--authenticator". Available: "%s".', implode('", "', $authenticators)));
    }

    /** @param array<string, mixed> $securityData */
    private function interactAuthenticatorQuestions(InputInterface $input, ConsoleStyle $io, InteractiveSecurityHelper $interactiveSecurityHelper, array $securityData): void
    {
        if ($input->getOption('authenticator')) {
            return;
        }

        // get list of authenticators
        $authenticators = $interactiveSecurityHelper->getAuthenticatorsFromConfig($securityData['security']['firewalls'] ?? []);

        if (!$authenticators) {
            $io->note('No authenticators found - so your user won\'t be automatically authenticated after registering.');

            return;
        }

        $input->setOption('authenticator', (string) (
            1 === \count($authenticators) ? $authenticators[0] : $io->choice(
                'Which authenticator should be used to login the user?',
                $authenticators
            )
        ));
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $securityHelper = new InteractiveSecurityHelper();
        $securityData = $this->readSecurityData();
        $providersData = $securityData['security']['providers'] ?? [];

        $userClass = $input->getOption('user-class') ?: $securityHelper->findUserClass($providersData);

        if (!$userClass) {
            throw new RuntimeCommandException('The User class cannot be guessed from "security.yaml", pass it with "--user-class".');
        }

        $usernameField = $input->getOption('username-field') ?: $securityHelper->findUserNameField($userClass, $providersData);

        if (!$usernameField) {
            throw new RuntimeCommandException(\sprintf('The login field of "%s" cannot be guessed, pass it with "--username-field".', $userClass));
        }

        $passwordField = $input->getOption('password-field') ?: $securityHelper->findPasswordField($userClass);

        if (!$passwordField) {
            throw new RuntimeCommandException(\sprintf('The password property of "%s" cannot be guessed, pass it with "--password-field".', $userClass));
        }

        $addUniqueEntityConstraint = (bool) $input->getOption('unique-entity');
        $willVerifyEmail = (bool) $input->getOption('verify-email');
        $verifyEmailAnonymously = (bool) $input->getOption('verify-anonymously');
        $idGetter = $emailGetter = $fromEmailAddress = $fromEmailName = null;

        if ($willVerifyEmail) {
            $idGetter = $input->getOption('id-getter') ?: $securityHelper->findIdGetter($userClass);
            $emailGetter = $input->getOption('email-getter') ?: $securityHelper->findEmailGetter($userClass, 'email');

            if (!$idGetter) {
                throw new RuntimeCommandException(\sprintf('"%s" has no "getId()" method, pass the getter with "--id-getter".', $userClass));
            }

            if (!$emailGetter) {
                throw new RuntimeCommandException(\sprintf('"%s" has no "getEmail()" method, pass the getter with "--email-getter".', $userClass));
            }

            $fromEmailAddress = Validator::validateEmailAddress($input->getOption('from-email-address'));
            $fromEmailName = Validator::notBlank($input->getOption('from-email-name'));
        }

        $autoLoginAuthenticator = $this->resolveAuthenticator($input, $io, $securityHelper, $securityData);
        $redirectRouteName = $input->getOption('redirect-route');

        $userClassNameDetails = $generator->createClassNameDetails(
            '\\'.$userClass,
            'Entity\\'
        );

        $userDoctrineDetails = $this->doctrineHelper->createDoctrineDetails($userClassNameDetails->getFullName());

        $userRepoVars = [
            'repository_full_class_name' => EntityManagerInterface::class,
            'repository_class_name' => 'EntityManagerInterface',
            'repository_var' => '$manager',
        ];

        $userRepository = $userDoctrineDetails->getRepositoryClass();

        if (null !== $userRepository) {
            $userRepoClassDetails = $generator->createClassNameDetails('\\'.$userRepository, 'Repository\\', 'Repository');

            $userRepoVars = [
                'repository_full_class_name' => $userRepoClassDetails->getFullName(),
                'repository_class_name' => $userRepoClassDetails->getShortName(),
                'repository_var' => \sprintf('$%s', lcfirst($userRepoClassDetails->getShortName())),
            ];
        }

        $verifyEmailServiceClassNameDetails = $generator->createClassNameDetails(
            'EmailVerifier',
            'Security\\'
        );

        $verifyEmailVars = ['will_verify_email' => $willVerifyEmail];

        if ($willVerifyEmail) {
            $verifyEmailVars = [
                'will_verify_email' => $willVerifyEmail,
                'email_verifier_class_details' => $verifyEmailServiceClassNameDetails,
                'verify_email_anonymously' => $verifyEmailAnonymously,
                'from_email' => $fromEmailAddress,
                'from_email_name' => addslashes($fromEmailName),
                'email_getter' => $emailGetter,
            ];

            $useStatements = new UseStatementGenerator([
                EntityManagerInterface::class,
                TemplatedEmail::class,
                Request::class,
                MailerInterface::class,
                UserInterface::class,
                VerifyEmailExceptionInterface::class,
                VerifyEmailHelperInterface::class,
                $userClassNameDetails->getFullName(),
            ]);

            $generator->generateClass(
                $verifyEmailServiceClassNameDetails->getFullName(),
                'verifyEmail/EmailVerifier.tpl.php',
                array_merge([
                    'use_statements' => $useStatements,
                    'id_getter' => $idGetter,
                    'email_getter' => $emailGetter,
                    'verify_email_anonymously' => $verifyEmailAnonymously,
                    'user_class_name' => $userClassNameDetails->getShortName(),
                ],
                    $userRepoVars
                )
            );

            $generator->generateTemplate(
                'registration/confirmation_email.html.twig',
                'registration/twig_email.tpl.php'
            );
        }

        // 1) Generate the form class
        $formClassDetails = $this->generateFormClass(
            $userClassNameDetails,
            $generator,
            $usernameField
        );

        // 2) Generate the controller
        $controllerClassNameDetails = $generator->createClassNameDetails(
            'RegistrationController',
            'Controller\\'
        );

        $useStatements = new UseStatementGenerator([
            AbstractController::class,
            $formClassDetails->getFullName(),
            $userClassNameDetails->getFullName(),
            Request::class,
            Response::class,
            Route::class,
            UserPasswordHasherInterface::class,
            EntityManagerInterface::class,
        ]);

        if ($willVerifyEmail) {
            $useStatements->addUseStatement([
                $verifyEmailServiceClassNameDetails->getFullName(),
                TemplatedEmail::class,
                Address::class,
                VerifyEmailExceptionInterface::class,
            ]);

            if ($verifyEmailAnonymously) {
                $useStatements->addUseStatement($userRepoVars['repository_full_class_name']);
            }
        }

        $autoLoginVars = [
            'login_after_registration' => null !== $autoLoginAuthenticator,
        ];

        if (null !== $autoLoginAuthenticator) {
            $useStatements->addUseStatement([
                Security::class,
            ]);

            $autoLoginVars['firewall'] = $autoLoginAuthenticator->firewallName;
            $autoLoginVars['authenticator'] = \sprintf('\'%s\'', $autoLoginAuthenticator->type->value);

            if (AuthenticatorType::CUSTOM === $autoLoginAuthenticator->type) {
                $useStatements->addUseStatement($autoLoginAuthenticator->authenticatorClass);
                $autoLoginVars['authenticator'] = \sprintf('%s::class', Str::getShortClassName($autoLoginAuthenticator->authenticatorClass));
            }
        }

        if ($isTranslatorAvailable = class_exists(Translator::class)) {
            $useStatements->addUseStatement(TranslatorInterface::class);
        }

        $generator->generateController(
            $controllerClassNameDetails->getFullName(),
            'registration/RegistrationController.tpl.php',
            array_merge([
                'use_statements' => $useStatements,
                'route_path' => '/register',
                'route_name' => 'app_register',
                'form_class_name' => $formClassDetails->getShortName(),
                'user_class_name' => $userClassNameDetails->getShortName(),
                'password_field' => $passwordField,
                'redirect_route_name' => $redirectRouteName ?? null,
                'translator_available' => $isTranslatorAvailable,
            ],
                $userRepoVars,
                $autoLoginVars,
                $verifyEmailVars,
            )
        );

        // 3) Generate the template
        $generator->generateTemplate(
            'registration/register.html.twig',
            'registration/twig_template.tpl.php',
            [
                'username_field' => $usernameField,
                'will_verify_email' => $willVerifyEmail,
            ]
        );

        // 4) Update the User class if necessary
        if ($addUniqueEntityConstraint) {
            $classDetails = new ClassDetails($userClass);
            $userManipulator = new ClassSourceManipulator(
                sourceCode: file_get_contents($classDetails->getPath())
            );
            $userManipulator->setIo($io);

            if ($this->doctrineHelper->isDoctrineSupportingAttributes()) {
                $userManipulator->addAttributeToClass(
                    UniqueEntity::class,
                    ['fields' => [$usernameField], 'message' => \sprintf('There is already an account with this %s', $usernameField)]
                );
            }

            $this->fileManager->dumpFile($classDetails->getPath(), $userManipulator->getSourceCode());
        }

        if ($willVerifyEmail) {
            $classDetails = new ClassDetails($userClass);
            $userManipulator = new ClassSourceManipulator(
                sourceCode: file_get_contents($classDetails->getPath()),
                overwrite: false,
            );
            $userManipulator->setIo($io);

            $userManipulator->addProperty(
                name: 'isVerified',
                defaultValue: false,
                attributes: [$userManipulator->buildAttributeNode(attributeClass: Column::class, options: [], attributePrefix: 'ORM')],
                propertyType: 'bool'
            );
            $userManipulator->addAccessorMethod('isVerified', 'isVerified', 'bool', false);
            $userManipulator->addSetter('isVerified', 'bool', false);

            $this->fileManager->dumpFile($classDetails->getPath(), $userManipulator->getSourceCode());
        }

        // Generate PHPUnit Tests
        if ($this->shouldGenerateTests($input)) {
            $testClassDetails = $generator->createClassNameDetails(
                'RegistrationControllerTest',
                'Test\\'
            );

            $useStatements = new UseStatementGenerator([
                EntityManager::class,
                KernelBrowser::class,
                TemplatedEmail::class,
                WebTestCase::class,
                $userRepoVars['repository_full_class_name'],
            ]);

            $generator->generateFile(
                targetPath: \sprintf('tests/%s.php', $testClassDetails->getShortName()),
                templateName: $willVerifyEmail ? 'registration/Test.WithVerify.tpl.php' : 'registration/Test.WithoutVerify.tpl.php',
                variables: array_merge([
                    'use_statements' => $useStatements,
                    'from_email' => $fromEmailAddress ?? null,
                ], $userRepoVars)
            );

            if (!class_exists(WebTestCase::class)) {
                $io->caution('You\'ll need to install the `symfony/test-pack` to execute the tests for your new controller.');
            }
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $this->successMessage($io, $willVerifyEmail, $userClassNameDetails->getShortName());
    }

    private function successMessage(ConsoleStyle $io, bool $emailVerification, string $userClass): void
    {
        $closing[] = 'Next:';

        if (!$emailVerification) {
            $closing[] = 'Make any changes you need to the form, controller & template.';
        } else {
            $index = 1;
            if ($missingPackagesMessage = $this->getMissingComponentsComposerMessage()) {
                $closing[] = '1) Install some missing packages:';
                $closing[] = \sprintf('     <fg=green>%s</>', $missingPackagesMessage);
                ++$index;
            }

            $closing[] = \sprintf('%d) In <fg=yellow>RegistrationController::verifyUserEmail()</>:', $index++);
            $closing[] = '   * Customize the last <fg=yellow>redirectToRoute()</> after a successful email verification.';
            $closing[] = '   * Make sure you\'re rendering <fg=yellow>success</> flash messages or change the <fg=yellow>$this->addFlash()</> line.';
            $closing[] = \sprintf('%d) Review and customize the form, controller, and templates as needed.', $index++);
            $closing[] = \sprintf('%d) Run <fg=yellow>"%s make:migration"</> to generate a migration for the newly added <fg=yellow>%s::isVerified</> property.', $index++, CliOutputHelper::getCommandPrefix(), $userClass);
        }

        $io->text($closing);
        $io->newLine();
        $io->text('Then open your browser, go to "/register" and enjoy your new form!');
        $io->newLine();
    }

    private function checkComponentsExist(ConsoleStyle $io): void
    {
        $message = $this->getMissingComponentsComposerMessage();

        if ($message) {
            $io->warning([
                'We\'re missing some important components. Don\'t forget to install these after you\'re finished.',
                $message,
            ]);
        }
    }

    private function getMissingComponentsComposerMessage(): ?string
    {
        $missing = false;
        $composerMessage = 'composer require';

        // verify-email-bundle 1.17.0 includes the new validateEmailConfirmationFromRequest method.
        // we need to check that if the bundle is installed, it is version 1.17.0 or greater
        if (class_exists(SymfonyCastsVerifyEmailBundle::class)) {
            $reflectedComponents = new \ReflectionClass(VerifyEmailHelper::class);

            if (!$reflectedComponents->hasMethod('validateEmailConfirmationFromRequest')) {
                throw new RuntimeCommandException('Please upgrade symfonycasts/verify-email-bundle to version 1.17.0 or greater.');
            }
        } else {
            $missing = true;
            $composerMessage = \sprintf('%s symfonycasts/verify-email-bundle', $composerMessage);
        }

        if (!interface_exists(MailerInterface::class)) {
            $missing = true;
            $composerMessage = \sprintf('%s symfony/mailer', $composerMessage);
        }

        if (!$missing) {
            return null;
        }

        return $composerMessage;
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            AbstractType::class,
            'form'
        );

        $dependencies->addClassDependency(
            Validation::class,
            'validator'
        );

        $dependencies->addClassDependency(
            TwigBundle::class,
            'twig-bundle'
        );

        $dependencies->addClassDependency(
            DoctrineBundle::class,
            'orm'
        );

        $dependencies->addClassDependency(
            SecurityBundle::class,
            'security'
        );
    }

    private function generateFormClass(ClassNameDetails $userClassDetails, Generator $generator, string $usernameField): ClassNameDetails
    {
        $formClassDetails = $generator->createClassNameDetails(
            'RegistrationFormType',
            'Form\\'
        );

        $formFields = [
            $usernameField => null,
            'agreeTerms' => [
                'type' => CheckboxType::class,
                'options_code' => <<<EOF
                                    'mapped' => false,
                                    'constraints' => [
                                        new IsTrue(
                                            message: 'You should agree to our terms.',
                                        ),
                                    ],
                    EOF
            ],
            'plainPassword' => [
                'type' => PasswordType::class,
                'options_code' => <<<EOF
                                    // instead of being set onto the object directly,
                                    // this is read and encoded in the controller
                                    'mapped' => false,
                                    'attr' => ['autocomplete' => 'new-password'],
                                    'constraints' => [
                                        new NotBlank(
                                            message: 'Please enter a password',
                                        ),
                                        new Length(
                                            min: 6,
                                            minMessage: 'Your password should be at least {{ limit }} characters',
                                            // max length allowed by Symfony for security reasons
                                            max: 4096,
                                        ),
                                    ],
                    EOF
            ],
        ];

        $this->formTypeRenderer->render(
            $formClassDetails,
            $formFields,
            $userClassDetails,
            [
                'Symfony\Component\Validator\Constraints\IsTrue',
                'Symfony\Component\Validator\Constraints\Length',
                'Symfony\Component\Validator\Constraints\NotBlank',
            ]
        );

        return $formClassDetails;
    }
}
