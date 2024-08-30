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
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassData;
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeController extends AbstractMaker
{
    public function __construct(private ?PhpCompatUtil $phpCompatUtil = null)
    {
        if (null !== $phpCompatUtil) {
            @trigger_deprecation(
                'symfony/maker-bundle',
                '1.55.0',
                \sprintf('Initializing MakeCommand while providing an instance of "%s" is deprecated. The $phpCompatUtil param will be removed in a future version.', PhpCompatUtil::class)
            );
        }
    }

    public static function getCommandName(): string
    {
        return 'make:controller';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a new controller class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('controller-class', InputArgument::OPTIONAL, \sprintf('Choose a name for your controller class (e.g. <fg=yellow>%sController</>)', Str::asClassName(Str::getRandomTerm())))
            ->addOption('no-template', null, InputOption::VALUE_NONE, 'Use this option to disable template generation')
            ->addOption('invokable', 'i', InputOption::VALUE_NONE, 'Use this option to create an invokable controller')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeController.txt'))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $controllerClassNameDetails = $generator->createClassNameDetails(
            $input->getArgument('controller-class'),
            'Controller\\',
            'Controller'
        );

        $withTemplate = $this->isTwigInstalled() && !$input->getOption('no-template');
        $isInvokable = (bool) $input->getOption('invokable');

        $controllerClass = $input->getArgument('controller-class');

        $controllerClassData = ClassData::create(
            class: '\\' === $controllerClass[0] ? substr($controllerClass, 1) : \sprintf('Controller\%s', $input->getArgument('controller-class')),
            suffix: 'Controller',
            extendsClass: AbstractController::class,
            useStatements: [
                $withTemplate ? Response::class : JsonResponse::class,
                Route::class,
            ]
        );

        //        dd([
        //            $controllerClassNameDetails,
        //            $controllerClassNameDetails->getRelativeName(),
        //            $controllerClassNameDetails->getShortName(),
        //            $controllerClassNameDetails->getFullName(),
        //            $controllerClassNameDetails->getRelativeNameWithoutSuffix(),
        //        ],
        //            [
        //                $controllerClassData,
        //                $controllerClassData->getClassName(relative: true),
        //                $controllerClassData->getClassName(),
        //                $controllerClassData->getFullClassName(),
        //                $controllerClassData->getClassName(relative: true, withoutSuffix: true),
        //            ]
        //        );

        //        $templateName = Str::asFilePath($controllerClassNameDetails->getRelativeNameWithoutSuffix())
        $templateName = Str::asFilePath($controllerClassData->getClassName(relative: true, withoutSuffix: true))
            .($isInvokable ? '.html.twig' : '/index.html.twig');

        $controllerPath = $generator->generateController(
            $controllerClassData->getFullClassName(),
            'controller/Controller.tpl.php',
            [
                'class_data' => $controllerClassData,
                //                'use_statements' => $useStatements,
                //                'route_path' => Str::asRoutePath($controllerClassNameDetails->getRelativeNameWithoutSuffix()),
                'route_path' => Str::asRoutePath($controllerClassData->getClassName(relative: true, withoutSuffix: true)),
                'route_name' => Str::AsRouteName($controllerClassData->getClassName(relative: true, withoutSuffix: true)),
                //                'route_name' => Str::asRouteName($controllerClassNameDetails->getRelativeNameWithoutSuffix()),
                'method_name' => $isInvokable ? '__invoke' : 'index',
                'with_template' => $withTemplate,
                'template_name' => $templateName,
            ]
        );

        if ($withTemplate) {
            $generator->generateTemplate(
                $templateName,
                'controller/twig_template.tpl.php',
                [
                    'controller_path' => $controllerPath,
                    'root_directory' => $generator->getRootDirectory(),
                    'class_name' => $controllerClassData->getClassName(),
                ]
            );
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text('Next: Open your new controller class and add some pages!');
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    private function isTwigInstalled(): bool
    {
        return class_exists(TwigBundle::class);
    }
}
