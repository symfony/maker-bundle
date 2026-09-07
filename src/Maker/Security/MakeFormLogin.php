<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker\Security;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Maker\Common\CanGenerateTestsTrait;
use Symfony\Bundle\MakerBundle\Security\InteractiveSecurityHelper;
use Symfony\Bundle\MakerBundle\Security\SecurityConfigUpdater;
use Symfony\Bundle\MakerBundle\Security\SecurityControllerBuilder;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Yaml\Yaml;

/**
 * Generate Form Login Security using SecurityBundle's Authenticator.
 *
 * @see https://symfony.com/doc/current/security.html#form-login
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class MakeFormLogin extends AbstractMaker
{
    use CanGenerateTestsTrait;

    private const SECURITY_CONFIG_PATH = 'config/packages/security.yaml';

    public function __construct(
        private FileManager $fileManager,
        private SecurityConfigUpdater $securityConfigUpdater,
        private SecurityControllerBuilder $securityControllerBuilder,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:security:form-login';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addOption('controller-name', null, InputOption::VALUE_REQUIRED, 'The name of the controller class to create (e.g. <fg=yellow>SecurityController</>)')
            ->addOption('firewall-name', null, InputOption::VALUE_REQUIRED, 'The firewall to update in <fg=yellow>security.yaml</>')
            ->addOption('user-class', null, InputOption::VALUE_REQUIRED, 'The class of the user to authenticate (e.g. <fg=yellow>App\\Entity\\User</>)')
            ->addOption('username-field', null, InputOption::VALUE_REQUIRED, 'The property people enter when logging in (e.g. <fg=yellow>email</>)')
            ->addOption('logout', null, InputOption::VALUE_NONE, 'Generate a <fg=yellow>/logout</> URL')
            ->setHelp($this->getHelpFileContents('security/MakeFormLogin.txt'))
        ;

        $this->configureCommandWithTestsOption($command);
    }

    public static function getCommandDescription(): string
    {
        return 'Generate the code needed for the form_login authenticator';
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            SecurityBundle::class,
            'security'
        );

        $dependencies->addClassDependency(TwigBundle::class, 'twig');

        // needed to update the YAML files
        $dependencies->addClassDependency(
            Yaml::class,
            'yaml'
        );

        $dependencies->addClassDependency(DoctrineBundle::class, 'orm');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $securityData = $this->readSecurityData();
        $providers = $securityData['security']['providers'];

        if (!$input->getOption('controller-name')) {
            $input->setOption('controller-name', $io->ask(
                'Choose a name for the controller class (e.g. <fg=yellow>SecurityController</>)',
                'SecurityController',
                Validator::validateClassName(...)
            ));
        }

        $securityHelper = new InteractiveSecurityHelper();

        if (!$input->getOption('firewall-name')) {
            $input->setOption('firewall-name', $securityHelper->guessFirewallName($io, $securityData));
        }

        if (!$input->getOption('user-class')) {
            $input->setOption('user-class', $securityHelper->guessUserClass($io, $providers));
        }

        if (!$input->getOption('username-field')) {
            $input->setOption('username-field', $securityHelper->guessUserNameField($io, $input->getOption('user-class'), $providers));
        }

        if (!$input->getOption('logout')) {
            $input->setOption('logout', $io->confirm('Do you want to generate a \'/logout\' URL?'));
        }

        $this->interactSetGenerateTests($input, $io);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $ysm = new YamlSourceManipulator($this->fileManager->getFileContents(self::SECURITY_CONFIG_PATH));
        $securityData = $this->readSecurityData();
        $providers = $securityData['security']['providers'];
        $securityHelper = new InteractiveSecurityHelper();

        $controllerName = $input->getOption('controller-name') ?: 'SecurityController';
        $firewallToUpdate = $input->getOption('firewall-name') ?: $securityHelper->findFirewallName($securityData);
        $userClass = $input->getOption('user-class') ?: $securityHelper->findUserClass($providers);
        $willLogout = $input->getOption('logout');

        if (!$userClass) {
            throw new RuntimeCommandException('The user class cannot be guessed from "security.yaml", pass it with "--user-class".');
        }

        $userNameField = $input->getOption('username-field') ?: $securityHelper->findUserNameField($userClass, $providers);

        if (!$userNameField) {
            throw new RuntimeCommandException(\sprintf('The login field of "%s" cannot be guessed, pass it with "--username-field".', $userClass));
        }

        $useStatements = new UseStatementGenerator([
            AbstractController::class,
            Response::class,
            Route::class,
            AuthenticationUtils::class,
        ]);

        $controllerNameDetails = $generator->createClassNameDetails($controllerName, 'Controller\\', 'Controller');
        $templatePath = strtolower($controllerNameDetails->getRelativeNameWithoutSuffix());

        $controllerPath = $generator->generateController(
            $controllerNameDetails->getFullName(),
            'security/formLogin/LoginController.tpl.php',
            [
                'use_statements' => $useStatements,
                'controller_name' => $controllerNameDetails->getShortName(),
                'template_path' => $templatePath,
            ]
        );

        if ($willLogout) {
            $manipulator = new ClassSourceManipulator($generator->getFileContentsForPendingOperation($controllerPath));

            $this->securityControllerBuilder->addLogoutMethod($manipulator);

            $generator->dumpFile($controllerPath, $manipulator->getSourceCode());
        }

        $generator->generateTemplate(
            \sprintf('%s/login.html.twig', $templatePath),
            'security/formLogin/login_form.tpl.php',
            [
                'logout_setup' => $willLogout,
                'username_label' => Str::asHumanWords($userNameField),
                'username_is_email' => false !== stripos($userNameField, 'email'),
            ]
        );

        $securityData = $this->securityConfigUpdater->updateForFormLogin($ysm->getContents(), $firewallToUpdate, 'app_login', 'app_login');

        if ($willLogout) {
            $securityData = $this->securityConfigUpdater->updateForLogout($securityData, $firewallToUpdate);
        }

        if ($this->shouldGenerateTests($input)) {
            $userClassNameDetails = $generator->createClassNameDetails(
                '\\'.$userClass,
                'Entity\\'
            );

            $testClassDetails = $generator->createClassNameDetails(
                'LoginControllerTest',
                'Test\\',
            );

            $useStatements = new UseStatementGenerator([
                $userClassNameDetails->getFullName(),
                KernelBrowser::class,
                EntityManager::class,
                WebTestCase::class,
                UserPasswordHasherInterface::class,
            ]);

            $generator->generateFile(
                targetPath: \sprintf('tests/%s.php', $testClassDetails->getShortName()),
                templateName: 'security/formLogin/Test.LoginController.tpl.php',
                variables: [
                    'use_statements' => $useStatements,
                    'user_class' => $userClass,
                    'user_short_name' => $userClassNameDetails->getShortName(),
                ],
            );

            if (!class_exists(WebTestCase::class)) {
                $io->caution('You\'ll need to install the `symfony/test-pack` to execute the tests for your new controller.');
            }
        }

        $generator->dumpFile(self::SECURITY_CONFIG_PATH, $securityData);

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            \sprintf('Next: Review and adapt the login template: <info>%s/login.html.twig</info> to suit your needs.', $templatePath),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function readSecurityData(): array
    {
        if (!$this->fileManager->fileExists(self::SECURITY_CONFIG_PATH)) {
            throw new RuntimeCommandException(\sprintf('The file "%s" does not exist. PHP & XML configuration formats are currently not supported.', self::SECURITY_CONFIG_PATH));
        }

        $securityData = (new YamlSourceManipulator($this->fileManager->getFileContents(self::SECURITY_CONFIG_PATH)))->getData();

        if (!isset($securityData['security']['providers']) || !$securityData['security']['providers']) {
            throw new RuntimeCommandException('To generate a form login authentication, you must configure at least one entry under "providers" in "security.yaml".');
        }

        return $securityData;
    }
}
