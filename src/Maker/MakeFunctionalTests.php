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

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
final class MakeFunctionalTests extends AbstractMaker
{
    private $router;
    private $kernel;
    private $fileManager;
    private $rolesHierachy;

    public function __construct(RouterInterface $router, KernelInterface $kernel, FileManager $fileManager, $rolesHierachy = null)
    {
        $this->router = $router;
        $this->kernel = $kernel;
        $this->fileManager = $fileManager;
        $this->rolesHierachy = $rolesHierachy;
    }

    public static function getCommandName(): string
    {
        return 'make:functional-tests';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf)
    {
        $command
            ->setDescription('Creates HTTP functional tests based on the routes defined in your application')
            ->addArgument('route-name', InputArgument::OPTIONAL, 'The name of the route to generate functional tests for (e.g. <fg=yellow>homepage</>)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'If used, this command will be allowed to overwrite previously generated tests files')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeFunctionalTests.txt'))
        ;

        $inputConf->setArgumentAsNonInteractive('route-name');
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $force = $input->getOption('force');
        $routeName = $input->getArgument('route-name');

        $this->generateTestBaseClass($generator, $force);

        /** @var Route[]|RouteCollection $routes */
        $routes = $this->router->getRouteCollection();

        if ($routeName) {
            $route = $routes->get($routeName);
            if (!$route) {
                throw new \InvalidArgumentException(sprintf('Cannot find route %s - is it enabled only in some environments?', $routeName));
            }

            $routes = [$routeName => $route];
        }

        $actions = $this->createAppActionsList($routes);
        $uncoveredActions = $this->findUncoverdActions($actions);

        foreach ($uncoveredActions as $action) {
            $this->generateActionTests($generator, $action, $force);
        }

        $hasOperations = $generator->hasPendingOperations();
        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        if ($hasOperations) {
            $io->text([
                'Next:',
                '  1. Open the <fg=yellow>App\Tests\Controller\WebTestCase</> class and customize how users are created',
                '  2. Open the generated test classes to adapt the functional tests to your needs',
                '',
                'You can read more about functional tests at <fg=yellow>https://symfony.com/doc/current/testing.html#functional-tests</>',
            ]);

            return;
        }

        $io->text([
            'No HTTP functional test was created, all your actions already have a test!',
            'You can read more about functional tests at <fg=yellow>https://symfony.com/doc/current/testing.html#functional-tests</>',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }

    private function generateTestBaseClass(Generator $generator, $force)
    {
        $details = $generator->createClassNameDetails('WebTestCase', 'Tests\\Controller\\');
        $testFile = $this->fileManager->getRelativePathForFutureClass($details->getFullName());

        if (!$force && $this->fileManager->fileExists($testFile)) {
            return;
        }

        $generator->generateClass(
            $details->getFullName(),
            'test/FunctionalBaseClass.tpl.php',
            ['roles' => $this->createRolesList($this->rolesHierachy)],
            $force
        );
    }

    private function createRolesList($rolesHierarchy): array
    {
        $roles = [];

        if (!is_array($rolesHierarchy)) {
            return $roles;
        }

        foreach ($rolesHierarchy as $parent => $children) {
            $roles[] = $parent;
            $roles = array_merge($roles, $children);
        }

        $roles = array_filter(array_unique($roles), function ($role) {
            return 'ROLE_ALLOWED_TO_SWITCH' !== $role;
        });

        $list = [];
        foreach ($roles as $role) {
            $list[Str::asCamelCase(str_replace('role_', '', strtolower($role)))] = $role;
        }

        return $list;
    }

    /**
     * @param RouteCollection|Route[] $routes
     *
     * @return string[]
     */
    private function createAppActionsList($routes): array
    {
        $actions = [];
        foreach ($routes as $name => $route) {
            if (0 === strpos($name, '_')) {
                continue;
            }

            $controllerName = $route->getDefault('_controller');
            if (!$controllerName || 0 !== strpos($controllerName, 'App\\')) {
                continue;
            }

            $actions[] = [
                'route_name' => $name,
                'route' => $route,
                'controller' => $controllerName,
            ];
        }

        return $actions;
    }

    private function findUncoverdActions(array $actions): array
    {
        $rootPath = $this->fileManager->absolutizePath('tests/Controller');

        if (!file_exists($rootPath)) {
            return $actions;
        }

        /** @var SplFileInfo[] $files */
        $files = $this->fileManager->createFinder($this->fileManager->absolutizePath('tests/Controller'))
            ->files()
            ->name('*.php')
        ;

        $covered = [];
        foreach ($files as $file) {
            preg_match_all('/@covers\s+(.+)/', file_get_contents($file->getPathname()), $matches);

            if (empty($matches[1])) {
                continue;
            }

            foreach ($matches[1] as $match) {
                $covered[trim($match, '\\() ')] = true;
            }
        }

        $uncovered = [];
        foreach ($actions as $action) {
            if (!isset($covered[$action['controller']])) {
                $uncovered[] = $action;
            }
        }

        return $uncovered;
    }

    private function generateActionTests(Generator $generator, $action, $force)
    {
        if (!$testName = $this->createTestName($action['route'])) {
            return;
        }

        $details = $generator->createClassNameDetails($testName, 'Tests\\');
        $testFile = $this->fileManager->getRelativePathForFutureClass($details->getFullName());

        if (!$force && $this->fileManager->fileExists($testFile)) {
            return;
        }

        $parameters = [
            'controller' => $action['controller'],
            'route_path' => $action['route']->getPath(),
            'route_methods' => $this->getRouteMethods($action['route']),
            'is_http_get_200' => $this->httpGetReturns200($action['route_name'], $action['route']),
        ];

        $generator->generateClass($details->getFullName(), 'test/Functional.tpl.php', $parameters, $force);
    }

    private function createTestName(Route $route): string
    {
        // App\Controller\User\AccountController::informations => Tests\Controller\User\AccountController\InformationsTest
        $parts = explode('::', $route->getDefault('_controller'));

        if (2 === count($parts) && 0 === strpos($parts[0], 'App\\')) {
            return str_replace('App\\', '', $parts[0]).'\\'.Str::asCamelCase($parts[1]).'Test';
        }

        return '';
    }

    private function getRouteMethods(Route $route): array
    {
        $methods = $route->getMethods() ?: ['GET'];

        return array_combine(array_map('ucfirst', array_map('strtolower', $methods)), $methods);
    }

    private function httpGetReturns200(string $routeName, Route $route): bool
    {
        // GET method not handled
        if ($route->getMethods() && !in_array('GET', $route->getMethods(), true)) {
            return false;
        }

        // The route can't be generated
        try {
            $url = $this->router->generate($routeName);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        // The kernel is not able to handle the request
        try {
            $response = $this->kernel->handle(Request::create($url, 'GET'));
        } catch (\Exception $e) {
            return false;
        }

        return 200 === $response->getStatusCode();
    }
}
