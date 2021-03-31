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

use Doctrine\Common\Annotations\Annotation;
use PhpParser\Builder\Param;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Doctrine\EntityClassGenerator;
use Symfony\Bundle\MakerBundle\Doctrine\ORMDependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\RelationManyToOne;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Security\InteractiveSecurityHelper;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Yaml\Yaml;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestTrait;
use SymfonyCasts\Bundle\ResetPassword\Persistence\Repository\ResetPasswordRequestRepositoryTrait;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelper;
use SymfonyCasts\Bundle\ResetPassword\SymfonyCastsResetPasswordBundle;

/**
 * @author Romaric Drigon <romaric.drigon@gmail.com>
 * @author Jesse Rushlow  <jr@rushlow.dev>
 * @author Ryan Weaver    <ryan@symfonycasts.com>
 * @author Antoine Michelet <jean.marcel.michelet@gmail.com>
 *
 * @internal
 * @final
 */
class MakeResetPassword extends AbstractMaker
{
    private $fileManager;
    private $doctrineHelper;
    private $entityClassGenerator;

    private $fromEmailAddress;
    private $fromEmailName;
    private $controllerResetSuccessRedirect;
    private $userClass;
    private $emailPropertyName;
    private $emailGetterMethodName;
    private $passwordSetterMethodName;

    public function __construct(FileManager $fileManager, DoctrineHelper $doctrineHelper, EntityClassGenerator $entityClassGenerator)
    {
        $this->fileManager = $fileManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->entityClassGenerator = $entityClassGenerator;
    }

    public static function getCommandName(): string
    {
        return 'make:reset-password';
    }

    public static function getCommandDescription(): string
    {
        return 'Create controller, entity, and repositories for use with symfonycasts/reset-password-bundle';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeResetPassword.txt'))
        ;
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(SymfonyCastsResetPasswordBundle::class, 'symfonycasts/reset-password-bundle');
        $dependencies->addClassDependency(MailerInterface::class, 'symfony/mailer');

        ORMDependencyBuilder::buildDependencies($dependencies);

        $dependencies->addClassDependency(Annotation::class, 'annotations');

        // reset-password-bundle 1.6 includes the ability to generate a fake token.
        // we need to check that version 1.6 is installed
        if (class_exists(ResetPasswordHelper::class) && !method_exists(ResetPasswordHelper::class, 'generateFakeResetToken')) {
            throw new RuntimeCommandException('Please run "composer upgrade symfonycasts/reset-password-bundle". Version 1.6 or greater of this bundle is required.');
        }
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $io->title('Let\'s make a password reset feature!');

        $interactiveSecurityHelper = new InteractiveSecurityHelper();

        if (!$this->fileManager->fileExists($path = 'config/packages/security.yaml')) {
            throw new RuntimeCommandException('The file "config/packages/security.yaml" does not exist. This command needs that file to accurately build the reset password form.');
        }

        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($path));
        $securityData = $manipulator->getData();
        $providersData = $securityData['security']['providers'] ?? [];

        $this->userClass = $interactiveSecurityHelper->guessUserClass(
            $io,
            $providersData,
            'What is the User entity that should be used with the "forgotten password" feature? (e.g. <fg=yellow>App\\Entity\\User</>)'
        );

        $this->emailPropertyName = $interactiveSecurityHelper->guessEmailField($io, $this->userClass);
        $this->emailGetterMethodName = $interactiveSecurityHelper->guessEmailGetter($io, $this->userClass, $this->emailPropertyName);
        $this->passwordSetterMethodName = $interactiveSecurityHelper->guessPasswordSetter($io, $this->userClass);

        $io->text(sprintf('Implementing reset password for <info>%s</info>', $this->userClass));

        $io->section('- ResetPasswordController -');
        $io->text('A named route is used for redirecting after a successful reset. Even a route that does not exist yet can be used here.');

        $this->controllerResetSuccessRedirect = $io->ask(
            'What route should users be redirected to after their password has been successfully reset?',
            'app_home',
            [Validator::class, 'notBlank']
        );

        $io->section('- Email -');
        $emailText[] = 'These are used to generate the email code. Don\'t worry, you can change them in the code later!';
        $io->text($emailText);

        $this->fromEmailAddress = $io->ask(
            'What email address will be used to send reset confirmations? e.g. mailer@your-domain.com',
            null,
            [Validator::class, 'validateEmailAddress']
        );

        $this->fromEmailName = $io->ask(
            'What "name" should be associated with that email address? e.g. "Acme Mail Bot"',
            null,
            [Validator::class, 'notBlank']
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $userClassNameDetails = $generator->createClassNameDetails(
            '\\'.$this->userClass,
            'Entity\\'
        );

        $controllerClassNameDetails = $generator->createClassNameDetails(
            'ResetPasswordController',
            'Controller\\'
        );

        $requestClassNameDetails = $generator->createClassNameDetails(
            'ResetPasswordRequest',
            'Entity\\'
        );

        $repositoryClassNameDetails = $generator->createClassNameDetails(
            'ResetPasswordRequestRepository',
            'Repository\\'
        );

        $requestFormTypeClassNameDetails = $generator->createClassNameDetails(
            'ResetPasswordRequestFormType',
            'Form\\'
        );

        $changePasswordFormTypeClassNameDetails = $generator->createClassNameDetails(
            'ChangePasswordFormType',
            'Form\\'
        );

        $generator->generateController(
            $controllerClassNameDetails->getFullName(),
            'resetPassword/ResetPasswordController.tpl.php',
            [
                'user_full_class_name' => $userClassNameDetails->getFullName(),
                'user_class_name' => $userClassNameDetails->getShortName(),
                'request_form_type_full_class_name' => $requestFormTypeClassNameDetails->getFullName(),
                'request_form_type_class_name' => $requestFormTypeClassNameDetails->getShortName(),
                'reset_form_type_full_class_name' => $changePasswordFormTypeClassNameDetails->getFullName(),
                'reset_form_type_class_name' => $changePasswordFormTypeClassNameDetails->getShortName(),
                'password_setter' => $this->passwordSetterMethodName,
                'success_redirect_route' => $this->controllerResetSuccessRedirect,
                'from_email' => $this->fromEmailAddress,
                'from_email_name' => $this->fromEmailName,
                'email_getter' => $this->emailGetterMethodName,
                'email_field' => $this->emailPropertyName,
            ]
        );

        $this->generateRequestEntity($generator, $requestClassNameDetails, $repositoryClassNameDetails);

        $this->setBundleConfig($io, $generator, $repositoryClassNameDetails->getFullName());

        $generator->generateClass(
            $requestFormTypeClassNameDetails->getFullName(),
            'resetPassword/ResetPasswordRequestFormType.tpl.php',
            [
                'email_field' => $this->emailPropertyName,
            ]
        );

        $generator->generateClass(
            $changePasswordFormTypeClassNameDetails->getFullName(),
            'resetPassword/ChangePasswordFormType.tpl.php'
        );

        $generator->generateTemplate(
            'reset_password/check_email.html.twig',
            'resetPassword/twig_check_email.tpl.php'
        );

        $generator->generateTemplate(
            'reset_password/email.html.twig',
            'resetPassword/twig_email.tpl.php'
        );

        $generator->generateTemplate(
            'reset_password/request.html.twig',
            'resetPassword/twig_request.tpl.php',
            [
                'email_field' => $this->emailPropertyName,
            ]
        );

        $generator->generateTemplate(
            'reset_password/reset.html.twig',
            'resetPassword/twig_reset.tpl.php'
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $this->successMessage($input, $io, $requestClassNameDetails->getFullName());
    }

    private function setBundleConfig(ConsoleStyle $io, Generator $generator, string $repositoryClassFullName): void
    {
        $configFileExists = $this->fileManager->fileExists($path = 'config/packages/reset_password.yaml');

        /*
         * reset_password.yaml does not exist, we assume flex was present when
         * the bundle was installed & a customized configuration is in use.
         * Remind the developer to set the repository class accordingly.
         */
        if (!$configFileExists) {
            $io->text(sprintf('We can\'t find %s. That\'s ok, you probably have a customized configuration.', $path));
            $io->text('Just remember to set the <fg=yellow>request_password_repository</> in your configuration.');
            $io->newLine();

            return;
        }

        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($path));
        $data = $manipulator->getData();

        $symfonyCastsKey = 'symfonycasts_reset_password';

        /*
         * reset_password.yaml exists, and was probably created by flex;
         * Let's replace it with a "clean" file.
         */
        if (1 >= \count($data[$symfonyCastsKey])) {
            $yaml = [
                $symfonyCastsKey => [
                    'request_password_repository' => $repositoryClassFullName,
                ],
            ];

            $generator->dumpFile($path, Yaml::dump($yaml));

            return;
        }

        /*
         * reset_password.yaml exists and appears to have been customized
         * before running make:reset-password. Let's just change the repository
         * value and preserve everything else.
         */
        $data[$symfonyCastsKey]['request_password_repository'] = $repositoryClassFullName;

        $manipulator->setData($data);

        $generator->dumpFile($path, $manipulator->getContents());
    }

    private function successMessage(InputInterface $input, ConsoleStyle $io, string $requestClassName): void
    {
        $closing[] = 'Next:';
        $closing[] = sprintf('  1) Run <fg=yellow>"php bin/console make:migration"</> to generate a migration for the new <fg=yellow>"%s"</> entity.', $requestClassName);
        $closing[] = '  2) Review forms in <fg=yellow>"src/Form"</> to customize validation and labels.';
        $closing[] = '  3) Review and customize the templates in <fg=yellow>`templates/reset_password`</>.';
        $closing[] = '  4) Make sure your <fg=yellow>MAILER_DSN</> env var has the correct settings.';
        $closing[] = '  5) Create a "forgot your password link" to the <fg=yellow>app_forgot_password_request</> route on your login form.';

        $io->text($closing);
        $io->newLine();
        $io->text('Then open your browser, go to "/reset-password" and enjoy!');
        $io->newLine();
    }

    private function generateRequestEntity(Generator $generator, ClassNameDetails $requestClassNameDetails, ClassNameDetails $repositoryClassNameDetails): void
    {
        $requestEntityPath = $this->entityClassGenerator->generateEntityClass($requestClassNameDetails, false, false, false);

        $generator->writeChanges();

        $manipulator = new ClassSourceManipulator(
            $this->fileManager->getFileContents($requestEntityPath)
        );

        $manipulator->addInterface(ResetPasswordRequestInterface::class);

        $manipulator->addTrait(ResetPasswordRequestTrait::class);

        $manipulator->addConstructor([
            (new Param('user'))->setType('object')->getNode(),
            (new Param('expiresAt'))->setType('\DateTimeInterface')->getNode(),
            (new Param('selector'))->setType('string')->getNode(),
            (new Param('hashedToken'))->setType('string')->getNode(),
        ], <<<'CODE'
<?php
$this->user = $user;
$this->initialize($expiresAt, $selector, $hashedToken);
CODE
        );

        $manipulator->addManyToOneRelation((new RelationManyToOne())
            ->setPropertyName('user')
            ->setTargetClassName($this->userClass)
            ->setMapInverseRelation(false)
            ->setCustomReturnType('object', false)
            ->avoidSetter()
        );

        $this->fileManager->dumpFile($requestEntityPath, $manipulator->getSourceCode());

        $this->entityClassGenerator->generateRepositoryClass(
            $repositoryClassNameDetails->getFullName(),
            $requestClassNameDetails->getFullName(),
            false,
            false
        );

        $generator->writeChanges();

        $pathRequestRepository = $this->fileManager->getRelativePathForFutureClass(
            $repositoryClassNameDetails->getFullName()
        );

        $manipulator = new ClassSourceManipulator(
            $this->fileManager->getFileContents($pathRequestRepository)
        );

        $manipulator->addInterface(ResetPasswordRequestRepositoryInterface::class);

        $manipulator->addTrait(ResetPasswordRequestRepositoryTrait::class);

        $methodBuilder = $manipulator->createMethodBuilder('createResetPasswordRequest', ResetPasswordRequestInterface::class, false);

        $manipulator->addMethodBuilder($methodBuilder, [
            (new Param('user'))->setType('object')->getNode(),
            (new Param('expiresAt'))->setType('\DateTimeInterface')->getNode(),
            (new Param('selector'))->setType('string')->getNode(),
            (new Param('hashedToken'))->setType('string')->getNode(),
        ], <<<'CODE'
<?php
return new ResetPasswordRequest($user, $expiresAt, $selector, $hashedToken);
CODE
        );

        $this->fileManager->dumpFile($pathRequestRepository, $manipulator->getSourceCode());
    }
}
