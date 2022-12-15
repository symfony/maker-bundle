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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Generate Form Login Security using SecurityBundle's Authenticator.
 *
 * @see https://symfony.com/doc/current/security.html#form-login
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class MakeFormLogin extends AbstractSecurityMaker
{
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
        $dependencies->addClassDependency(TwigBundle::class, 'twig');

        parent::configureDependencies($dependencies);
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        parent::interact($input, $io, $command);

        $securityData = $this->ysm->getData();

        if (!isset($securityData['security']['providers']) || !$securityData['security']['providers']) {
            throw new RuntimeCommandException('To generate a form login authentication, you must configure at least one entry under "providers" in "security.yaml".');
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $useStatements = new UseStatementGenerator([
            AbstractController::class,
            Response::class,
            Route::class,
            AuthenticationUtils::class,
        ]);

        $controllerNameDetails = $generator->createClassNameDetails($this->securityControllerName, 'Controller\\', 'Controller');
        $templatePath = strtolower($controllerNameDetails->getRelativeNameWithoutSuffix());

        $controllerPath = $this->fileManager->getRelativePathForFutureClass($controllerNameDetails->getFullName());

        $controllerExists = $this->fileManager->fileExists($controllerPath);

        if (!$controllerExists) {
            $generator->generateController(
                $controllerNameDetails->getFullName(),
                'EmptyController.tpl.php',
                [
                    'use_statements' => $useStatements,
                    'controller_name' => $controllerNameDetails->getShortName(),
                ]
            );
        }

        $controllerSource = $controllerExists ? file_get_contents($controllerPath) : $generator->getFileContentsForPendingOperation($controllerPath);

        $manipulator = new ClassSourceManipulator($controllerSource);

        $this->securityControllerBuilder->addFormLoginMethod($manipulator, $templatePath);

        $securityData = $this->securityConfigUpdater->updateForFormLogin($this->ysm->getContents(), $this->firewallToUpdate, 'app_login', 'app_login');

        if ($this->willLogout) {
            $this->securityControllerBuilder->addLogoutMethod($manipulator);

            $securityData = $this->securityConfigUpdater->updateForLogout($securityData, $this->firewallToUpdate);
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

        $generator->dumpFile(self::SECURITY_CONFIG_PATH, $securityData);
        $generator->dumpFile($controllerPath, $manipulator->getSourceCode());

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            sprintf('Next: Review and adapt the login template: <info>%s/login.html.twig</info> to suit your needs.', $templatePath),
        ]);
    }
}
