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
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Yaml\Yaml;

/**
 * WIP - Security System.
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class MakeFormLogin extends AbstractMaker
{
    private const SECURITY_CONFIG_PATH = 'config/packages/security.yaml';
    private YamlSourceManipulator $ysm;
    private array $securityData = [];
    private string $firewallToUpdate;
    private string $userNameField;
    private bool $willLogout;

    public function __construct(
        private FileManager $fileManager,
        private SecurityConfigUpdater $securityConfigUpdater,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:security:form-login';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        // TODO: Implement configureCommand() method.
    }

    public static function getCommandDescription(): string
    {
        // TODO: Ya this needs to be better
        return 'Something better needs to go here';
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
        $this->securityData = $this->ysm->getData();

        if (!isset($this->securityData['security']['providers']) || !$this->securityData['security']['providers']) {
            throw new RuntimeCommandException('To generate a form login authentication, you must configure at least one entry under "providers" in "security.yaml".');
        }

        $securityHelper = new InteractiveSecurityHelper();
        $this->firewallToUpdate = $securityHelper->guessFirewallName($io, $this->securityData);
        $userClass = $securityHelper->guessUserClass($io, $this->securityData['security']['providers']);
        $this->userNameField = $securityHelper->guessUserNameField($io, $userClass, $this->securityData['security']['providers']);
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

        $controllerNameDetails = $generator->createClassNameDetails('LoginController', 'Controller\\', 'Controller');

        $generator->generateController(
            $controllerNameDetails->getFullName(),
            'security/formLogin/LoginController.tpl.php',
            ['use_statements' => $useStatements]
        );

        $generator->generateTemplate(
            'login/login.html.twig',
            'security/formLogin/login_form.tpl.php',
            [
                'logout_setup' => $this->willLogout,
                'username_field' => $this->userNameField,
                'username_label' => Str::asHumanWords($this->userNameField),
                'username_is_email' => false !== stripos($this->userNameField, 'email'),
            ]
        );

        $securityData = $this->securityConfigUpdater->updateForFormLogin($this->ysm->getContents(), 'app_login', 'app_login');
        $generator->dumpFile(self::SECURITY_CONFIG_PATH, $securityData);

        $generator->writeChanges();
    }
}
