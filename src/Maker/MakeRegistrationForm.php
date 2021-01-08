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
use Doctrine\Common\Annotations\Annotation;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Security\InteractiveSecurityHelper;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassDetails;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validation;
use SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents;
use SymfonyCasts\Bundle\VerifyEmail\SymfonyCastsVerifyEmailBundle;

/**
 * @author Ryan Weaver   <ryan@symfonycasts.com>
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class MakeRegistrationForm extends AbstractMaker
{
    private $fileManager;

    private $formTypeRenderer;

    private $router;

    private $doctrineHelper;

    public function __construct(FileManager $fileManager, FormTypeRenderer $formTypeRenderer, RouterInterface $router, DoctrineHelper $doctrineHelper)
    {
        $this->fileManager = $fileManager;
        $this->formTypeRenderer = $formTypeRenderer;
        $this->router = $router;
        $this->doctrineHelper = $doctrineHelper;
    }

    public static function getCommandName(): string
    {
        return 'make:registration-form';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new registration form system')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeRegistrationForm.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        // initialize arguments & commands that are internal (i.e. meant only to be asked)
        $command
            ->addArgument('user-class')
            ->addArgument('username-field')
            ->addArgument('password-field')
            ->addArgument('will-verify-email')
            ->addArgument('verify-email-anonymously')
            ->addArgument('id-getter')
            ->addArgument('email-getter')
            ->addArgument('from-email-address')
            ->addArgument('from-email-name')
            ->addOption('auto-login-authenticator')
            ->addOption('firewall-name')
            ->addOption('redirect-route-name')
            ->addOption('add-unique-entity-constraint')
        ;

        $interactiveSecurityHelper = new InteractiveSecurityHelper();

        if (!$this->fileManager->fileExists($path = 'config/packages/security.yaml')) {
            throw new RuntimeCommandException('The file "config/packages/security.yaml" does not exist. This command needs that file to accurately build your registration form.');
        }

        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($path));
        $securityData = $manipulator->getData();
        $providersData = $securityData['security']['providers'] ?? [];

        $input->setArgument(
            'user-class',
            $userClass = $interactiveSecurityHelper->guessUserClass(
                $io,
                $providersData,
                'Enter the User class that you want to create during registration (e.g. <fg=yellow>App\\Entity\\User</>)'
            )
        );
        $io->text(sprintf('Creating a registration form for <info>%s</info>', $userClass));

        $input->setArgument(
            'username-field',
            $interactiveSecurityHelper->guessUserNameField($io, $userClass, $providersData)
        );

        $input->setArgument(
            'password-field',
            $interactiveSecurityHelper->guessPasswordField($io, $userClass)
        );

        // see if it makes sense to add the UniqueEntity constraint
        $userClassDetails = new ClassDetails($userClass);
        $addAnnotation = false;
        if (!$userClassDetails->doesDocBlockContainAnnotation('@UniqueEntity')) {
            $addAnnotation = $io->confirm(sprintf('Do you want to add a <comment>@UniqueEntity</comment> validation annotation on your <comment>%s</comment> class to make sure duplicate accounts aren\'t created?', Str::getShortClassName($userClass)));
        }
        $input->setOption(
            'add-unique-entity-constraint',
            $addAnnotation
        );

        $willVerify = $io->confirm('Do you want to send an email to verify the user\'s email address after registration?', true);

        $input->setArgument('will-verify-email', $willVerify);

        // This must be preset to true to avoid code being generated if $willVerify === false
        $input->setArgument('verify-email-anonymously', false);

        if ($willVerify) {
            $this->checkComponentsExist($io);

            $emailText[] = 'By default, users are required to be authenticated when they click the verification link that is emailed to them.';
            $emailText[] = 'This prevents the user from registering on their laptop, then clicking the link on their phone, without';
            $emailText[] = 'having to log in. To allow multi device email verification, we can embed a user id in the verification link.';
            $io->text($emailText);
            $io->newLine();
            $input->setArgument(
                'verify-email-anonymously',
                $io->confirm('Would you like to include the user id in the verification link to allow anonymous email verification?', false)
            );

            $input->setArgument('id-getter', $interactiveSecurityHelper->guessIdGetter($io, $userClass));
            $input->setArgument('email-getter', $interactiveSecurityHelper->guessEmailGetter($io, $userClass, 'email'));

            $input->setArgument('from-email-address', $io->ask(
                'What email address will be used to send registration confirmations? e.g. mailer@your-domain.com',
                null,
                [Validator::class, 'validateEmailAddress']
            ));

            $input->setArgument('from-email-name', $io->ask(
                'What "name" should be associated with that email address? e.g. "Acme Mail Bot"',
                null,
                [Validator::class, 'notBlank']
            ));
        }

        if ($io->confirm('Do you want to automatically authenticate the user after registration?')) {
            $this->interactAuthenticatorQuestions(
                $input,
                $io,
                $interactiveSecurityHelper,
                $securityData,
                $command
            );
        }

        if (!$input->getOption('auto-login-authenticator')) {
            $routeNames = array_keys($this->router->getRouteCollection()->all());
            $input->setOption(
                'redirect-route-name',
                $io->choice(
                    'What route should the user be redirected to after registration?',
                    $routeNames
                )
            );
        }
    }

    private function interactAuthenticatorQuestions(InputInterface $input, ConsoleStyle $io, InteractiveSecurityHelper $interactiveSecurityHelper, array $securityData, Command $command)
    {
        $firewallsData = $securityData['security']['firewalls'] ?? [];
        $firewallName = $interactiveSecurityHelper->guessFirewallName(
            $io,
            $securityData,
            'Which firewall key in security.yaml holds the authenticator you want to use for logging in?'
        );

        if (!isset($firewallsData[$firewallName])) {
            $io->note('No firewalls found - skipping authentication after registration. You might want to configure your security before running this command.');

            return;
        }

        $input->setOption('firewall-name', $firewallName);

        // get list of guard authenticators
        $authenticatorClasses = $interactiveSecurityHelper->getAuthenticatorClasses($firewallsData[$firewallName]);
        if (empty($authenticatorClasses)) {
            $io->note('No Guard authenticators found - so your user won\'t be automatically authenticated after registering.');
        } else {
            $input->setOption(
                'auto-login-authenticator',
                1 === \count($authenticatorClasses) ? $authenticatorClasses[0] : $io->choice(
                    'Which authenticator\'s onAuthenticationSuccess() should be used after logging in?',
                    $authenticatorClasses
                )
            );
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $userClass = $input->getArgument('user-class');
        $userClassNameDetails = $generator->createClassNameDetails(
            '\\'.$userClass,
            'Entity\\'
        );

        $userDoctrineDetails = $this->doctrineHelper->createDoctrineDetails($userClassNameDetails->getFullName());

        $userRepoVars = [
            'repository_full_class_name' => 'Doctrine\ORM\EntityManagerInterface',
            'repository_class_name' => 'EntityManagerInterface',
            'repository_var' => '$manager',
        ];

        $userRepository = $userDoctrineDetails->getRepositoryClass();

        if (null !== $userRepository) {
            $userRepoClassDetails = $generator->createClassNameDetails('\\'.$userRepository, 'Repository\\', 'Repository');

            $userRepoVars = [
                'repository_full_class_name' => $userRepoClassDetails->getFullName(),
                'repository_class_name' => $userRepoClassDetails->getShortName(),
                'repository_var' => sprintf('$%s', lcfirst($userRepoClassDetails->getShortName())),
            ];
        }

        $verifyEmailServiceClassNameDetails = $generator->createClassNameDetails(
            'EmailVerifier',
            'Security\\'
        );

        if ($input->getArgument('will-verify-email')) {
            $generator->generateClass(
                $verifyEmailServiceClassNameDetails->getFullName(),
                'verifyEmail/EmailVerifier.tpl.php',
                array_merge([
                        'id_getter' => $input->getArgument('id-getter'),
                        'email_getter' => $input->getArgument('email-getter'),
                        'verify_email_anonymously' => $input->getArgument('verify-email-anonymously'),
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
        $usernameField = $input->getArgument('username-field');
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

        $authenticatorClassName = $input->getOption('auto-login-authenticator');
        $generator->generateController(
            $controllerClassNameDetails->getFullName(),
            'registration/RegistrationController.tpl.php',
            array_merge([
                    'route_path' => '/register',
                    'route_name' => 'app_register',
                    'form_class_name' => $formClassDetails->getShortName(),
                    'form_full_class_name' => $formClassDetails->getFullName(),
                    'user_class_name' => $userClassNameDetails->getShortName(),
                    'user_full_class_name' => $userClassNameDetails->getFullName(),
                    'password_field' => $input->getArgument('password-field'),
                    'will_verify_email' => $input->getArgument('will-verify-email'),
                    'verify_email_anonymously' => $input->getArgument('verify-email-anonymously'),
                    'verify_email_security_service' => $verifyEmailServiceClassNameDetails->getFullName(),
                    'from_email' => $input->getArgument('from-email-address'),
                    'from_email_name' => $input->getArgument('from-email-name'),
                    'email_getter' => $input->getArgument('email-getter'),
                    'authenticator_class_name' => $authenticatorClassName ? Str::getShortClassName($authenticatorClassName) : null,
                    'authenticator_full_class_name' => $authenticatorClassName,
                    'firewall_name' => $input->getOption('firewall-name'),
                    'redirect_route_name' => $input->getOption('redirect-route-name'),
                ],
                $userRepoVars
            )
        );

        // 3) Generate the template
        $generator->generateTemplate(
            'registration/register.html.twig',
            'registration/twig_template.tpl.php',
            [
                'username_field' => $usernameField,
            ]
        );

        // 4) Update the User class if necessary
        if ($input->getOption('add-unique-entity-constraint')) {
            $classDetails = new ClassDetails($userClass);
            $userManipulator = new ClassSourceManipulator(
                file_get_contents($classDetails->getPath())
            );
            $userManipulator->setIo($io);

            $userManipulator->addAnnotationToClass(
                UniqueEntity::class,
                [
                    'fields' => [$usernameField],
                    'message' => sprintf('There is already an account with this %s', $usernameField),
                ]
            );
            $this->fileManager->dumpFile($classDetails->getPath(), $userManipulator->getSourceCode());
        }

        if ($input->getArgument('will-verify-email')) {
            $classDetails = new ClassDetails($userClass);
            $userManipulator = new ClassSourceManipulator(
                file_get_contents($classDetails->getPath())
            );
            $userManipulator->setIo($io);

            $userManipulator->addProperty('isVerified', ['@ORM\Column(type="boolean")'], false);
            $userManipulator->addAccessorMethod('isVerified', 'isVerified', 'bool', false);
            $userManipulator->addSetter('isVerified', 'bool', false);

            $this->fileManager->dumpFile($classDetails->getPath(), $userManipulator->getSourceCode());
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $this->successMessage($io, $input->getArgument('will-verify-email'), $userClassNameDetails->getShortName());
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
                $closing[] = sprintf('     <fg=green>%s</>', $missingPackagesMessage);
                ++$index;
            }

            $closing[] = sprintf('%d) In <fg=yellow>RegistrationController::verifyUserEmail()</>:', $index++);
            $closing[] = '   * Customize the last <fg=yellow>redirectToRoute()</> after a successful email verification.';
            $closing[] = '   * Make sure you\'re rendering <fg=yellow>success</> flash messages or change the <fg=yellow>$this->addFlash()</> line.';
            $closing[] = sprintf('%d) Review and customize the form, controller, and templates as needed.', $index++);
            $closing[] = sprintf('%d) Run <fg=yellow>"php bin/console make:migration"</> to generate a migration for the newly added <fg=yellow>%s::isVerified</> property.', $index++, $userClass);
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

        // verify-email-bundle 1.1.1 includes support for translations and a fix for the bad expiration time bug.
        // we need to check that if the bundle is installed, it is version 1.1.1 or greater
        if (class_exists(SymfonyCastsVerifyEmailBundle::class)) {
            $reflectedComponents = new \ReflectionClass(VerifyEmailSignatureComponents::class);

            if (!$reflectedComponents->hasMethod('getExpirationMessageKey')) {
                throw new RuntimeCommandException('Please upgrade symfonycasts/verify-email-bundle to version 1.1.1 or greater.');
            }
        } else {
            $missing = true;
            $composerMessage = sprintf('%s symfonycasts/verify-email-bundle', $composerMessage);
        }

        if (!interface_exists(MailerInterface::class)) {
            $missing = true;
            $composerMessage = sprintf('%s symfony/mailer', $composerMessage);
        }

        if (!$missing) {
            return null;
        }

        return $composerMessage;
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Annotation::class,
            'doctrine/annotations'
        );

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
            'orm-pack'
        );

        $dependencies->addClassDependency(
            SecurityBundle::class,
            'security'
        );
    }

    private function generateFormClass(ClassNameDetails $userClassDetails, Generator $generator, string $usernameField)
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
