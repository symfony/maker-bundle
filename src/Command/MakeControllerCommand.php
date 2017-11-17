<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Command;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeControllerCommand extends AbstractCommand
{
    protected static $defaultName = 'make:controller';

    private $router;

    public function __construct(Generator $generator, RouterInterface $router)
    {
        $this->router = $router;

        parent::__construct($generator);
    }

    public function configure()
    {
        $this
            ->setDescription('Creates a new controller class')
            ->addArgument('controller-class', InputArgument::OPTIONAL, sprintf('Choose a name for your controller class (e.g. <fg=yellow>%sController</>)', Str::asClassName(Str::getRandomTerm())))
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeController.txt'))
        ;
    }

    protected function getParameters(): array
    {
        $controllerClassName = Str::asClassName($this->input->getArgument('controller-class'), 'Controller');
        Validator::validateClassName($controllerClassName);

        return [
            'controller_class_name' => $controllerClassName,
            'route_path' => Str::asRoutePath(str_replace('Controller', '', $controllerClassName)),
            'route_name' => Str::asRouteNAme(str_replace('Controller', '', $controllerClassName))
        ];
    }

    protected function getFiles(array $params): array
    {
        $skeletonFile = $this->isTwigInstalled() ? 'ControllerWithTwig.php.txt' : 'Controller.php.txt';

        return [
            __DIR__.'/../Resources/skeleton/controller/'.$skeletonFile => 'src/Controller/'.$params['controller_class_name'].'.php'
        ];
    }

    protected function getResultMessage(array $params): string
    {
        return sprintf('%s created!', $params['controller_class_name']);
    }

    protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        if (!count($this->router->getRouteCollection())) {
            $io->text('<error> Warning! </> No routes configuration defined yet.');
            $io->text('           You should probably uncomment the annotation routes in <comment>config/routes.yaml</>');
            $io->newLine();
        }
        $io->text('Next: Open your new controller class and add some pages!');
    }

    protected function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Route::class,
            'annotations'
        );
    }

    private function isTwigInstalled()
    {
        return class_exists(TwigBundle::class);
    }
}
