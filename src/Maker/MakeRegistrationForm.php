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
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Security\InteractiveSecurityHelper;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class MakeRegistrationForm extends AbstractMaker
{
    private $fileManager;

    private $formTypeRenderer;

    public function __construct(FileManager $fileManager, FormTypeRenderer $formTypeRenderer)
    {
        $this->fileManager = $fileManager;
        $this->formTypeRenderer = $formTypeRenderer;
    }

    public static function getCommandName(): string
    {
        return 'make:registration-form';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new registration form system')
            ->addArgument('user-class', InputArgument::OPTIONAL, 'Full user class')
            ->addArgument('username-field', InputArgument::OPTIONAL, 'Field on your User class used to login')
            ->addArgument('password-field', InputArgument::OPTIONAL, 'Field on your User class that stores the hashed password')
            ->addOption('auto-login-authenticator', null, InputOption::VALUE_REQUIRED, 'Authenticator class to use for logging in')
            ->addOption('firewall-name', null, InputOption::VALUE_REQUIRED, 'Firewall key used for authentication')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeRegistrationForm.txt'))
        ;

        $inputConf->setArgumentAsNonInteractive('user-class');
        $inputConf->setArgumentAsNonInteractive('username-field');
        $inputConf->setArgumentAsNonInteractive('password-field');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        $interactiveSecurityHelper = new InteractiveSecurityHelper();

        if (!$this->fileManager->fileExists($path = 'config/packages/security.yaml')) {
            throw new RuntimeCommandException('The file "config/packages/security.yaml" does not exist. This command needs that file to accurately build your registration form.');
        }

        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($path));
        $securityData = $manipulator->getData();
        $providersData = $securityData['security']['providers'];

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

        if ($io->confirm('Do you want to automatically authenticate the user after registration?')) {
            $this->interactAuthenticatorQuestions(
                $input,
                $io,
                $interactiveSecurityHelper,
                $securityData,
                $command
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
        }

        $input->setOption(
            'auto-login-authenticator',
            count($authenticatorClasses) === 1 ? $authenticatorClasses[0] : $io->choice(
                'Which authenticator\'s onAuthenticationSuccess() should be used after logging in?',
                $authenticatorClasses
            )
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $userClass = $input->getArgument('user-class');
        $userClassNameDetails = $generator->createClassNameDetails(
            '\\'.$userClass,
            'Entity\\'
        );

        $usernameField = $input->getArgument('username-field');
        $formClassDetails = $this->generateFormClass(
            $userClassNameDetails,
            $generator,
            $usernameField
        );

        $controllerClassNameDetails = $generator->createClassNameDetails(
            'RegistrationController',
            'Controller\\'
        );

        $authenticatorClassName = $input->getOption('auto-login-authenticator');
        $generator->generateController(
            $controllerClassNameDetails->getFullName(),
            'registration/RegistrationController.tpl.php',
            [
                'route_path' => '/register',
                'route_name' => 'app_register',
                'form_class_name' => $formClassDetails->getShortName(),
                'form_full_class_name' => $formClassDetails->getFullName(),
                'user_class_name' => $userClassNameDetails->getShortName(),
                'user_full_class_name' => $userClassNameDetails->getFullName(),
                'password_field' => $input->getArgument('password-field'),
                'authenticator_class_name' => $authenticatorClassName ? Str::getShortClassName($authenticatorClassName) : null,
                'authenticator_full_class_name' => $authenticatorClassName,
                'firewall_name' => $input->getOption('firewall-name'),
            ]
        );

        $generator->generateFile(
            'templates/registration/register.html.twig',
            'registration/twig_template.tpl.php',
            [
                'username_field' => $usernameField,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text('Next: Go to /register to check out your new form!');
        $io->text('Then, make any changes you need to the form, controller & template.');
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            // we only need doctrine/annotations, which contains
            // the recipe that loads annotation routes
            Annotation::class,
            'annotations'
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
            'plainPassword' => [
                'type' => PasswordType::class,
                'options' => [
                    'mapped' => false,
                    // TODO - NotBlank constraint
                ]
            ],
        ];

        $this->formTypeRenderer->render(
            $formClassDetails,
            $formFields,
            $userClassDetails
        );

        return $formClassDetails;
    }
}
