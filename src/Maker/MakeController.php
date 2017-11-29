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
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeController extends AbstractMaker
{
    private $router;
    private $projectDir;

    public function __construct(RouterInterface $router, string $projectDir)
    {
        $this->router = $router;
        $this->projectDir = $projectDir;
    }

    public static function getCommandName(): string
    {
        return 'make:controller';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates a new controller class')
            ->addArgument('controller-class', InputArgument::OPTIONAL, sprintf('Choose a name for your controller class (e.g. <fg=yellow>%sController</>)', Str::asClassName(Str::getRandomTerm())))
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeController.txt'))
        ;
    }

    public function getParameters(InputInterface $input): array
    {
        $controllerClassName = Str::asClassName($input->getArgument('controller-class'), 'Controller');
        Validator::validateClassName($controllerClassName);

        if (file_exists($this->projectDir.'/templates/base.html.twig')) {
            $twigFirstLine = "{% extends 'base.html.twig' %}\n\n{% block title %}Hello {{ controller_name }}!{% endblock %}\n";
        } else {
            $twigFirstLine = "<!DOCTYPE html>\n\n<title>Hello {{ controller_name }}!</title>\n";
        }

        return [
            'controller_class_name' => $controllerClassName,
            'controller_class_file' => 'src/Controller/'.$controllerClassName.'.php',
            'route_path' => Str::asRoutePath(str_replace('Controller', '', $controllerClassName)),
            'route_name' => Str::asRouteName(str_replace('Controller', '', $controllerClassName)),
            'twig_file' => Str::asFilePath(str_replace('Controller', '', $controllerClassName)).'.html.twig',
            'twig_first_line' => $twigFirstLine,
            'twig_installed' => $this->isTwigInstalled(),
        ];
    }

    public function getFiles(array $params): array
    {
        $dir = __DIR__.'/../Resources/skeleton/controller/';

        $paths = [$dir.'Controller.tpl.php' => $params['controller_class_file']];

        if ($params['twig_installed']) {
            $paths[$dir.'Controller.twig.php'] = 'templates/'.$params['twig_file'];
        }

        return $paths;
    }

    public function writeSuccessMessage(array $params, ConsoleStyle $io)
    {
        parent::writeSuccessMessage($params, $io);

        if (!count($this->router->getRouteCollection())) {
            $io->text('<error> Warning! </> No routes configuration defined yet.');
            $io->text('           You should probably uncomment the annotation routes in <comment>config/routes.yaml</>');
            $io->newLine();
        }
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
