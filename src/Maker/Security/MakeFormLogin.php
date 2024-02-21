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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
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
use Symfony\Component\HttpFoundation\Response;
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
    private const SECURITY_CONFIG_PATH = 'config/packages/security.yaml';
    private YamlSourceManipulator $ysm;
    private string $controllerName;
    private string $firewallToUpdate;
    private string $userNameField;
    private bool $willLogout;

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
        $command->setHelp(file_get_contents(\dirname(__DIR__, 2).'/Resources/help/security/MakeFormLogin.txt'));
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
        if (!$this->fileManager->fileExists(self::SECURITY_CONFIG_PATH)) {
            throw new RuntimeCommandException(sprintf('The file "%s" does not exist. PHP & XML configuration formats are currently not supported.', self::SECURITY_CONFIG_PATH));
        }

        $this->ysm = new YamlSourceManipulator($this->fileManager->getFileContents(self::SECURITY_CONFIG_PATH));
        $securityData = $this->ysm->getData();

        if (!isset($securityData['security']['providers']) || !$securityData['security']['providers']) {
            throw new RuntimeCommandException('To generate a form login authentication, you must configure at least one entry under "providers" in "security.yaml".');
        }

        $this->controllerName = $io->ask(
            'Choose a name for the controller class (e.g. <fg=yellow>SecurityController</>)',
            'SecurityController',
            Validator::validateClassName(...)
        );

        $securityHelper = new InteractiveSecurityHelper();
        $this->firewallToUpdate = $securityHelper->guessFirewallName($io, $securityData);
        $userClass = $securityHelper->guessUserClass($io, $securityData['security']['providers']);
        $this->userNameField = $securityHelper->guessUserNameField($io, $userClass, $securityData['security']['providers']);
        $this->willLogout = $io->confirm('Do you want to generate a \'/logout\' URL?');
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $useStatements = new UseStatementGenerator([
            AbstractController::class,
            Response::class,
            Route::class,
            AuthenticationUtils::class,
        ]);

        $controllerNameDetails = $generator->createClassNameDetails($this->controllerName, 'Controller\\', 'Controller');
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

        if ($this->willLogout) {
            $manipulator = new ClassSourceManipulator($generator->getFileContentsForPendingOperation($controllerPath));

            $this->securityControllerBuilder->addLogoutMethod($manipulator);

            $generator->dumpFile($controllerPath, $manipulator->getSourceCode());
        }

        $generator->generateTemplate(
            sprintf('%s/login.html.twig', $templatePath),
            'security/formLogin/login_form.tpl.php',
            [
                'logout_setup' => $this->willLogout,
                'username_label' => Str::asHumanWords($this->userNameField),
                'username_is_email' => false !== stripos($this->userNameField, 'email'),
            ]
        );

        $securityData = $this->securityConfigUpdater->updateForFormLogin($this->ysm->getContents(), $this->firewallToUpdate, 'app_login', 'app_login');

        if ($this->willLogout) {
            $securityData = $this->securityConfigUpdater->updateForLogout($securityData, $this->firewallToUpdate);
        }

        $generator->dumpFile(self::SECURITY_CONFIG_PATH, $securityData);

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            sprintf('Next: Review and adapt the login template: <info>%s/login.html.twig</info> to suit your needs.', $templatePath),
        ]);
    }
}
