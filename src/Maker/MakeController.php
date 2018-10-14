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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeController extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:controller';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new controller class')
            ->addArgument('controller-class', InputArgument::OPTIONAL, sprintf('Choose a name for your controller class (e.g. <fg=yellow>%sController</>)', Str::asClassName(Str::getRandomTerm())))
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Choose a path for your controller (e.g. <fg=yellow>"Foo/Bar"</>)')
            ->addOption('template', true, InputOption::VALUE_OPTIONAL, 'If this option is set to false we will skip template creation')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeController.txt'))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $ControllerPathName = $input->getOption('path');
        $Infolder = ($ControllerPathName) ? true : false;
        $SuffixName = ($Infolder) ? str_replace('/',"\\",$ControllerPathName) : 'Controller';
        $controllerClassNameDetails = $generator->createControllerClassNameDetails(
            $input->getArgument('controller-class'),
            'Controller\\',
            $SuffixName,
            $Infolder
        );
        $templateName = ($Infolder) ? 
                        Str::asFilePath($ControllerPathName).'/index.html.twig' : 
                        Str::asFilePath($controllerClassNameDetails->getRelativeNameWithoutSuffix()).'/index.html.twig';
        $controllerPath = $generator->generateClass(
            $controllerClassNameDetails->getFullName(),
            'controller/Controller.tpl.php',
            [
                'parent_class_name' => \method_exists(AbstractController::class, 'getParameter') ? 'AbstractController' : 'Controller',
                'route_path' => ($Infolder) ? str_replace("/controller","",Str::asRoutePath($controllerClassNameDetails->getRelativeNameWithoutSuffix())) : Str::asRoutePath($controllerClassNameDetails->getRelativeNameWithoutSuffix()),
                'route_name' => ($Infolder) ? str_replace("_controller","",Str::asRouteName($controllerClassNameDetails->getRelativeNameWithoutSuffix())) : Str::asRouteName($controllerClassNameDetails->getRelativeNameWithoutSuffix()),
                'twig_installed' => $this->isTwigInstalled(),
                'template_name' => $templateName,
            ]
        );

        if ($input->getOption('template') != false && $this->isTwigInstalled()) {
            $generator->generateFile(
                'templates/'.$templateName,
                'controller/twig_template.tpl.php',
                [
                    'controller_path' => $controllerPath,
                ]
            );
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text('Next: Open your new controller class and add some pages!');
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            // we only need doctrine/annotations, which contains
            // the recipe that loads annotation routes
            Annotation::class,
            'annotations'
        );
    }

    private function isTwigInstalled()
    {
        return class_exists(TwigBundle::class);
    }
}
