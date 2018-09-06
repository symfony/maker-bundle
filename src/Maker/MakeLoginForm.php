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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Security\SecurityConfigUpdater;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
final class MakeLoginForm extends AbstractMaker
{
    private $fileManager;

    private $configUpdater;

    public function __construct(FileManager $fileManager, SecurityConfigUpdater $configUpdater)
    {
        $this->fileManager   = $fileManager;
        $this->configUpdater = $configUpdater;
    }

    public static function getCommandName(): string
    {
        return 'make:login-form';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates a login form and its controller and authenticator');
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $controllerClassNameDetails = $generator->createClassNameDetails(
            'SecurityController',
            'Controller\\',
            'Controller'
        );

        $templateName   = Str::asFilePath($controllerClassNameDetails->getRelativeNameWithoutSuffix()).'/login.html.twig';
        $controllerPath = $generator->generateClass(
            $controllerClassNameDetails->getFullName(),
            'login_form/SecurityController.tpl.php',
            [
                'parent_class_name' => \method_exists(AbstractController::class, 'getParameter') ? 'AbstractController' : 'Controller',
            ]
        );

        $generator->generateFile(
            'templates/'.$templateName,
            'login_form/login_form.tpl.php',
            [
                'controller_path' => $controllerPath,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            SecurityBundle::class,
            'security'
        );

        $dependencies->addClassDependency(
            TwigBundle::class,
            'twig'
        );
    }
}
