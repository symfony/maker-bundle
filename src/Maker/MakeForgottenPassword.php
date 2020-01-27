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
use Symfony\Bundle\MakerBundle\Doctrine\ORMDependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Security\InteractiveSecurityHelper;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validation;

/**
 * @author Romaric Drigon <romaric.drigon@gmail.com>
 *
 * @internal
 */
final class MakeForgottenPassword extends AbstractMaker
{
    private $fileManager;

    private $formTypeRenderer;

    private $router;

    public function __construct(FileManager $fileManager, FormTypeRenderer $formTypeRenderer, RouterInterface $router)
    {
        $this->fileManager = $fileManager;
        $this->formTypeRenderer = $formTypeRenderer;
        $this->router = $router;
    }

    public static function getCommandName(): string
    {
        return 'make:forgotten-password';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates a "forgotten password" mechanism')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeForgottenPassword.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        // initialize arguments & commands that are internal (i.e. meant only to be asked)
        $command
            ->addArgument('user-class')
            ->addArgument('email-field')
            ->addArgument('email-getter')
            ->addArgument('password-setter')
        ;

        $interactiveSecurityHelper = new InteractiveSecurityHelper();

        if (!$this->fileManager->fileExists($path = 'config/packages/security.yaml')) {
            throw new RuntimeCommandException('The file "config/packages/security.yaml" does not exist. This command needs that file to accurately build the forgotten password form.');
        }

        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($path));
        $securityData = $manipulator->getData();
        $providersData = $securityData['security']['providers'] ?? [];

        $input->setArgument(
            'user-class',
            $userClass = $interactiveSecurityHelper->guessUserClass(
                $io,
                $providersData,
                'Enter the User class that should be used with the "forgotten password" feature (e.g. <fg=yellow>App\\Entity\\User</>)'
            )
        );
        $io->text(sprintf('Implementing forgotten password for <info>%s</info>', $userClass));

        $input->setArgument(
            'email-field',
            $interactiveSecurityHelper->guessEmailField($io, $userClass)
        );
        $input->setArgument(
            'email-getter',
            $interactiveSecurityHelper->guessEmailGetter($io, $userClass)
        );
        $input->setArgument(
            'password-setter',
            $interactiveSecurityHelper->guessPasswordSetter($io, $userClass)
        );
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        // This recipe depends upon Doctrine ORM, to save the token and update the user
        ORMDependencyBuilder::buildDependencies($dependencies);

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
            SecurityBundle::class,
            'security'
        );
        $dependencies->addClassDependency(
            SwiftmailerBundle::class,
            'mail'
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $userClass = $input->getArgument('user-class');
        $userClassNameDetails = $generator->createClassNameDetails(
            '\\'.$userClass,
            'Entity\\'
        );
        $tokenClassNameDetails = $generator->createClassNameDetails(
            'PasswordResetToken',
            'Entity\\'
        );
        $repositoryClassNameDetails = $generator->createClassNameDetails(
            'PasswordResetTokenRepository',
            'Repository\\'
        );

        // 1) Create a new "PasswordResetToken" entity and its repository
        $generator->generateClass(
            $tokenClassNameDetails->getFullName(),
            'forgottenPassword/PasswordResetToken.tpl.php',
            [
                'repository_class_name' => $repositoryClassNameDetails->getFullName(),
                'user_class_name' => $userClassNameDetails->getShortName(),
                'user_full_class_name' => $userClassNameDetails->getFullName(),
            ]
        );
        $generator->generateClass(
            $repositoryClassNameDetails->getFullName(),
            'forgottenPassword/PasswordResetTokenRepository.tpl.php',
            [
                'token_class_name' => $tokenClassNameDetails->getShortName(),
                'token_full_class_name' => $tokenClassNameDetails->getFullName(),
                'user_class_name' => $userClassNameDetails->getShortName(),
                'user_full_class_name' => $userClassNameDetails->getFullName(),
            ]
        );

        // 2) Generate the "request" (email) form class
        $emailField = $input->getArgument('email-field');
        $requestFormClassDetails = $this->generateRequestFormClass(
            $generator,
            $emailField
        );

        // 3) Generate the "new password" form class
        $resettingFormClassDetails = $this->generateResettingFormClass($generator);

        // 4) Generate the controller
        $controllerClassNameDetails = $generator->createClassNameDetails(
            'ForgottenPasswordController',
            'Controller\\'
        );

        $generator->generateController(
            $controllerClassNameDetails->getFullName(),
            'forgottenPassword/ForgottenPasswordController.tpl.php',
            [
                'request_form_class_name' => $requestFormClassDetails->getShortName(),
                'request_form_full_class_name' => $requestFormClassDetails->getFullName(),
                'resetting_form_class_name' => $resettingFormClassDetails->getShortName(),
                'resetting_form_full_class_name' => $resettingFormClassDetails->getFullName(),
                'user_class_name' => $userClassNameDetails->getShortName(),
                'user_full_class_name' => $userClassNameDetails->getFullName(),
                'email_field' => $emailField,
                'email_getter' => $input->getArgument('email-getter'),
                'password_setter' => $input->getArgument('password-setter'),
                'login_route' => 'app_login',
                'token_class_name' => $tokenClassNameDetails->getShortName(),
                'token_full_class_name' => $tokenClassNameDetails->getFullName(),
            ]
        );

        // 5) Generate the "request" template
        $generator->generateFile(
            'templates/forgotten_password/request.html.twig',
            'forgottenPassword/twig_request.tpl.php',
            [
                'email_field' => $emailField,
            ]
        );

        // 6) Generate the reset e-mail template
        $generator->generateFile(
            'templates/forgotten_password/email.txt.twig',
            'forgottenPassword/twig_email.tpl.php',
            []
        );

        // 7) Generate the "checkEmail" template
        $generator->generateFile(
            'templates/forgotten_password/check_email.html.twig',
            'forgottenPassword/twig_check_email.tpl.php',
            []
        );

        // 8) Generate the "reset" template
        $generator->generateFile(
            'templates/forgotten_password/reset.html.twig',
            'forgottenPassword/twig_reset.tpl.php',
            []
        );

        $generator->writeChanges();
        $this->writeSuccessMessage($io);

        $io->text('Done! A new entity was added: PasswordResetToken. You should now generate a migration (make:migration) and run it to update your database.');
        $io->text('Next: Please review ForgottenPasswordController. Then you can add a link to "app_forgotten_password_request" path anywhere you like, typically below your login form!');
    }

    private function generateRequestFormClass(Generator $generator, string $emailField)
    {
        $formClassDetails = $generator->createClassNameDetails(
            'PasswordRequestFormType',
            'Form\\'
        );

        $formFields = [
            $emailField => [
                'type' => EmailType::class,
                'options_code' => <<<EOF
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your $emailField',
                    ]),
                ],
EOF
            ],
        ];

        $this->formTypeRenderer->render(
            $formClassDetails,
            $formFields,
            null,
            [
                'Symfony\Component\Validator\Constraints\NotBlank',
            ]
        );

        return $formClassDetails;
    }

    private function generateResettingFormClass(Generator $generator)
    {
        $formClassDetails = $generator->createClassNameDetails(
            'PasswordResettingFormType',
            'Form\\'
        );

        $formFields = [
            'plainPassword' => [
                'type' => RepeatedType::class,
                'options_code' => <<<EOF
                'type' => PasswordType::class,
                'first_options' => [
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
                    'label' => 'New password',
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
                ],
                'invalid_message' => 'The password fields must match.',
                // Instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
EOF
            ],
        ];

        $this->formTypeRenderer->render(
            $formClassDetails,
            $formFields,
            null,
            [
                'Symfony\Component\Validator\Constraints\Length',
                'Symfony\Component\Validator\Constraints\NotBlank',
            ],
            [
                PasswordType::class,
            ]
        );

        return $formClassDetails;
    }
}
