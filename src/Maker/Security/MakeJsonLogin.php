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
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Generate Form Login Security using SecurityBundle's Authenticator.
 *
 * @see https://symfony.com/doc/current/security.html#form-login
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class MakeJsonLogin extends AbstractSecurityMaker
{
    public static function getCommandName(): string
    {
        return 'make:security:json-login';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command->setHelp(file_get_contents(\dirname(__DIR__, 2).'/Resources/help/security/MakeJsonLogin.txt'));
    }

    public static function getCommandDescription(): string
    {
        return 'Generate the code needed for the json_login authenticator';
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $userClassDetails = new ClassNameDetails($this->userClass, '');

        $useStatements = new UseStatementGenerator([
            $userClassDetails->getFullName(),
            AbstractController::class,
            JsonResponse::class,
            Response::class,
            Route::class,
            CurrentUser::class,
        ]);

        $controllerNameDetails = $generator->createClassNameDetails($this->securityControllerName, 'Controller\\', 'Controller');

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

        $this->securityControllerBuilder->addJsonLoginMethod($manipulator, $userClassDetails);

        $securityData = $this->securityConfigUpdater->updateForJsonLogin($this->ysm->getContents(), $this->firewallToUpdate, 'app_api_login');

        if ($this->willLogout) {
            $this->securityControllerBuilder->addLogoutMethod($manipulator);

            $securityData = $this->securityConfigUpdater->updateForLogout($securityData, $this->firewallToUpdate);
        }

        $generator->dumpFile(self::SECURITY_CONFIG_PATH, $securityData);
        $generator->dumpFile($controllerPath, $manipulator->getSourceCode());

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Make a <info>POST</info> request to <info>/api/login</info> with a <info>username</info> and <info>password</info> to login.',
            'Then: The security system intercepts the requests and authenticates the user.',
            sprintf('And Finally: The <info>%s::apiLogin</info> method creates and returns a JsonResponse.', $controllerNameDetails->getShortName()),
        ]);
    }
}
