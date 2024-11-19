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

    private string $userClass;
    private string $usernameField;
    private string $passwordField;
    private bool $willVerifyEmail = false;
    private bool $verifyEmailAnonymously = false;
    private string $idGetter;
    private string $emailGetter;
    private string $fromEmailAddress;
    private string $fromEmailName;
    private ?Authenticator $autoLoginAuthenticator = null;
    private string $redirectRouteName;
    private bool $addUniqueEntityConstraint = false;

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

        $this->configureCommandWithTestsOption($command);
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $interactiveSecurityHelper = new InteractiveSecurityHelper();

        if (null === $this->router) {
            throw new RuntimeCommandException('Router have been explicitly disabled in your configuration. This command needs to use the router.');
        }

        if (!$this->fileManager->fileExists($path = 'config/packages/security.yaml')) {
            throw new RuntimeCommandException('The file "config/packages/security.yaml" does not exist. PHP & XML configuration formats are currently not supported.');
        }

        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($path));
        $securityData = $manipulator->getData();
        $providersData = $securityData['security']['providers'] ?? [];

        $this->userClass = $interactiveSecurityHelper->guessUserClass(
            $io,
            $providersData,
            'Enter the User class that you want to create during registration (e.g. <fg=yellow>App\\Entity\\User</>)'
        );
        $io->text(\sprintf('Creating a registration form for <info>%s</info>', $this->userClass));

        $this->usernameField = $interactiveSecurityHelper->guessUserNameField($io, $this->userClass, $providersData);

        $this->passwordField = $interactiveSecurityHelper->guessPasswordField($io, $this->userClass);

        // see if it makes sense to add the UniqueEntity constraint
        $userClassDetails = new ClassDetails($this->userClass);

        if (!$userClassDetails->hasAttribute(UniqueEntity::class)) {
            $this->addUniqueEntityConstraint = (bool) $io->confirm(\sprintf('Do you want to add a <comment>#[UniqueEntity]</comment> validation attribute to your <comment>%s</comment> class to make sure duplicate accounts aren\'t created?', Str::getShortClassName($this->userClass)));
        }

        $this->willVerifyEmail = (bool) $io->confirm('Do you want to send an email to verify the user\'s email address after registration?');

        if ($this->willVerifyEmail) {
            $this->checkComponentsExist($io);

            $emailText[] = 'By default, users are required to be authenticated when they click the verification link that is emailed to them.';
            $emailText[] = 'This prevents the user from registering on their laptop, then clicking the link on their phone, without';
            $emailText[] = 'having to log in. To allow multi device email verification, we can embed a user id in the verification link.';
            $io->text($emailText);
            $io->newLine();
            $this->verifyEmailAnonymously = (bool) $io->confirm('Would you like to include the user id in the verification link to allow anonymous email verification?', false);

            $this->idGetter = $interactiveSecurityHelper->guessIdGetter($io, $this->userClass);
            $this->emailGetter = $interactiveSecurityHelper->guessEmailGetter($io, $this->userClass, 'email');

            $this->fromEmailAddress = $io->ask(
                'What email address will be used to send registration confirmations? (e.g. <fg=yellow>mailer@your-domain.com</>)',
                null,
                Validator::validateEmailAddress(...)
            );

            $this->fromEmailName = $io->ask(
                'What "name" should be associated with that email address? (e.g. <fg=yellow>Acme Mail Bot</>)',
                null,
                Validator::notBlank(...)
            );
        }

        if ($io->confirm('Do you want to automatically authenticate the user after registration?')) {
            $this->interactAuthenticatorQuestions(
                $io,
                $interactiveSecurityHelper,
                $securityData
            );
        }

        if (!$this->autoLoginAuthenticator) {
            $routeNames = array_keys($this->router->getRouteCollection()->all());
            $this->redirectRouteName = $io->choice('What route should the user be redirected to after registration?', $routeNames);
        }

        $this->interactSetGenerateTests($input, $io);
    }

    /** @param array<string, mixed> $securityData */
    private function interactAuthenticatorQuestions(ConsoleStyle $io, InteractiveSecurityHelper $interactiveSecurityHelper, array $securityData): void
    {
        // get list of authenticators
        $authenticators = $interactiveSecurityHelper->getAuthenticatorsFromConfig($securityData['security']['firewalls'] ?? []);

        if (empty($authenticators)) {
            $io->note('No authenticators found - so your user won\'t be automatically authenticated after registering.');

            return;
        }

        $this->autoLoginAuthenticator =
            1 === \count($authenticators) ? $authenticators[0] : $io->choice(
                'Which authenticator should be used to login the user?',
                $authenticators
            );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $userClassNameDetails = $generator->createClassNameDetails(
            '\\'.$this->userClass,
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

        $verifyEmailVars = ['will_verify_email' => $this->willVerifyEmail];

        if ($this->willVerifyEmail) {
            $verifyEmailVars = [
                'will_verify_email' => $this->willVerifyEmail,
                'email_verifier_class_details' => $verifyEmailServiceClassNameDetails,
                'verify_email_anonymously' => $this->verifyEmailAnonymously,
                'from_email' => $this->fromEmailAddress,
                'from_email_name' => addslashes($this->fromEmailName),
                'email_getter' => $this->emailGetter,
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
                    'id_getter' => $this->idGetter,
                    'email_getter' => $this->emailGetter,
                    'verify_email_anonymously' => $this->verifyEmailAnonymously,
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
        $usernameField = $this->usernameField;
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

        if ($this->willVerifyEmail) {
            $useStatements->addUseStatement([
                $verifyEmailServiceClassNameDetails->getFullName(),
                TemplatedEmail::class,
                Address::class,
                VerifyEmailExceptionInterface::class,
            ]);

            if ($this->verifyEmailAnonymously) {
                $useStatements->addUseStatement($userRepoVars['repository_full_class_name']);
            }
        }

        $autoLoginVars = [
            'login_after_registration' => null !== $this->autoLoginAuthenticator,
        ];

        if (null !== $this->autoLoginAuthenticator) {
            $useStatements->addUseStatement([
                Security::class,
            ]);

            $autoLoginVars['firewall'] = $this->autoLoginAuthenticator->firewallName;
            $autoLoginVars['authenticator'] = \sprintf('\'%s\'', $this->autoLoginAuthenticator->type->value);

            if (AuthenticatorType::CUSTOM === $this->autoLoginAuthenticator->type) {
                $useStatements->addUseStatement($this->autoLoginAuthenticator->authenticatorClass);
                $autoLoginVars['authenticator'] = \sprintf('%s::class', Str::getShortClassName($this->autoLoginAuthenticator->authenticatorClass));
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
                'password_field' => $this->passwordField,
                'redirect_route_name' => $this->redirectRouteName ?? null,
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
                'will_verify_email' => $this->willVerifyEmail,
            ]
        );

        // 4) Update the User class if necessary
        if ($this->addUniqueEntityConstraint) {
            $classDetails = new ClassDetails($this->userClass);
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

        if ($this->willVerifyEmail) {
            $classDetails = new ClassDetails($this->userClass);
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
        if ($this->shouldGenerateTests()) {
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
                templateName: $this->willVerifyEmail ? 'registration/Test.WithVerify.tpl.php' : 'registration/Test.WithoutVerify.tpl.php',
                variables: array_merge([
                    'use_statements' => $useStatements,
                    'from_email' => $this->fromEmailAddress ?? null,
                ], $userRepoVars)
            );

            if (!class_exists(WebTestCase::class)) {
                $io->caution('You\'ll need to install the `symfony/test-pack` to execute the tests for your new controller.');
            }
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $this->successMessage($io, $this->willVerifyEmail, $userClassNameDetails->getShortName());
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
                                        new IsTrue([
                                            'message' => 'You should agree to our terms.',
                                        ]),
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
                                        new NotBlank([
                                            'message' => 'Please enter a password',
                                        ]),
                                        new Length([
                                            'min' => 6,
                                            'minMessage' => 'Your password should be at least {{ limit }} characters',
                                            // max length allowed by Symfony for security reasons
                                            'max' => 4096,
                                        ]),
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
