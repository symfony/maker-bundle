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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\Common\CanGenerateTestsTrait;
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
    use CanGenerateTestsTrait;

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

        $this->configureCommandWithTestsOption($command);
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $this->interactSetGenerateTests($input, $io);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $withTemplate = $this->isTwigInstalled() && !$input->getOption('no-template');
        $isInvokable = (bool) $input->getOption('invokable');

        $controllerClass = $input->getArgument('controller-class');
        $controllerClassName = \sprintf('Controller\%s', $controllerClass);

        // If the class name provided is absolute, we do not assume it will live in src/Controller
        // e.g. src/Custom/Location/For/MyController instead of src/Controller/MyController
        if ($isAbsolute = '\\' === $controllerClass[0]) {
            $controllerClassName = substr($controllerClass, 1);
        }

        $controllerClassData = ClassData::create(
            class: $controllerClassName,
            suffix: 'Controller',
            extendsClass: AbstractController::class,
            useStatements: [
                $withTemplate ? Response::class : JsonResponse::class,
                Route::class,
            ]
        );

        // Again if the class name is absolute, lets not make assumptions about where the twig template
        // should live. E.g. templates/custom/location/for/my_controller.html.twig instead of
        // templates/my/controller.html.twig. We do however remove the root_namespace prefix in either case
        // so we don't end up with templates/app/my/controller.html.twig
        $templateName = $isAbsolute ?
            $controllerClassData->getFullClassName(withoutRootNamespace: true, withoutSuffix: true) :
            $controllerClassData->getClassName(relative: true, withoutSuffix: true)
        ;

        // Convert the twig template name into a file path where it will be generated.
        $templatePath = \sprintf('%s%s', Str::asFilePath($templateName), $isInvokable ? '.html.twig' : '/index.html.twig');

        $controllerPath = $generator->generateClassFromClassData($controllerClassData, 'controller/Controller.tpl.php', [
            'route_path' => Str::asRoutePath($controllerClassData->getClassName(relative: true, withoutSuffix: true)),
            'route_name' => Str::AsRouteName($controllerClassData->getClassName(relative: true, withoutSuffix: true)),
            'method_name' => $isInvokable ? '__invoke' : 'index',
            'with_template' => $withTemplate,
            'template_name' => $templatePath,
        ], true);

        if ($withTemplate) {
            $generator->generateTemplate(
                $templatePath,
                'controller/twig_template.tpl.php',
                [
                    'controller_path' => $controllerPath,
                    'root_directory' => $generator->getRootDirectory(),
                    'class_name' => $controllerClassData->getClassName(),
                ]
            );
        }

        if ($this->shouldGenerateTests()) {
            $testClassData = ClassData::create(
                class: \sprintf('Tests\Controller\%s', $controllerClassData->getClassName(relative: true, withoutSuffix: true)),
                suffix: 'ControllerTest',
                extendsClass: WebTestCase::class,
                useStatements: [
                ]
            );

            $generator->generateClassFromClassData($testClassData, 'controller/test/Test.tpl.php', [
                'route_path' => Str::asRoutePath($controllerClassData->getClassName(relative: true, withoutSuffix: true)),
            ]);

            if (!class_exists(WebTestCase::class)) {
                $io->caution('You\'ll need to install the `symfony/test-pack` to execute the tests for your new controller.');
            }
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
