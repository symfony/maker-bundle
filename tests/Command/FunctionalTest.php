<?php

namespace Symfony\Bundle\MakerBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MakerBundle\Command\MakerCommand;
use Symfony\Bundle\MakerBundle\Maker\MakeAuthenticator;
use Symfony\Bundle\MakerBundle\Maker\MakeCommand;
use Symfony\Bundle\MakerBundle\Maker\MakeController;
use Symfony\Bundle\MakerBundle\Maker\MakeEntity;
use Symfony\Bundle\MakerBundle\Maker\MakeForm;
use Symfony\Bundle\MakerBundle\Maker\MakeFunctionalTest;
use Symfony\Bundle\MakerBundle\Maker\MakeSerializerEncoder;
use Symfony\Bundle\MakerBundle\Maker\MakeSubscriber;
use Symfony\Bundle\MakerBundle\Maker\MakeTwigExtension;
use Symfony\Bundle\MakerBundle\Maker\MakeUnitTest;
use Symfony\Bundle\MakerBundle\Maker\MakeValidator;
use Symfony\Bundle\MakerBundle\Maker\MakeVoter;
use Symfony\Bundle\MakerBundle\EventRegistry;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Routing\RouterInterface;

class FunctionalTest extends TestCase
{
    private $targetDir;

    public function setUp()
    {
        $tmpDir = sys_get_temp_dir().'/sf'.random_int(111111, 999999);
        @mkdir($tmpDir, 0777, true);

        $this->targetDir = $tmpDir;
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove($this->targetDir);
    }

    /**
     * @dataProvider getCommandTests
     */
    public function testCommands(MakerInterface $maker, array $inputs)
    {
        $command = new MakerCommand($maker, $this->createGenerator());

        $command->setCheckDependencies(false);

        $tester = new CommandTester($command);
        $tester->setInputs($inputs);
        $tester->execute(array());

        $this->assertContains('Success', $tester->getDisplay());

        $files = $this->parsePHPFiles($tester->getDisplay());
        foreach ($files as $file) {
            $process = new Process(sprintf('php vendor/bin/php-cs-fixer fix --dry-run --diff %s', $this->targetDir.'/'.$file), __DIR__.'/../../');
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \Exception(sprintf('File "%s" has a php-cs problem: %s', $file, $process->getOutput()));
            }
        }
    }

    public function getCommandTests()
    {
        $makers = array();

        $makers['command'] = array(
            new MakeCommand(),
            array(
                // command name
                'app:foo',
            ),
        );

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn(new RouteCollection());
        $makers['controller'] = array(
            new MakeController($router),
            array(
                // controller class name
                'FooBar',
            ),
        );

        $makers['entity'] = array(
            new MakeEntity(),
            array(
                // entity class name
                'FooBar',
            ),
        );

        $makers['form'] = array(
            new MakeForm(),
            array(
                // form name
                'FooBar',
            ),
        );

        $makers['functional'] = array(
            new MakeFunctionalTest(),
            array(
                // functional test class
                'FooBar',
            ),
        );

        $eventRegistry = $this->createMock(EventRegistry::class);
        $eventRegistry->expects($this->any())
            ->method('getAllActiveEvents')
            ->willReturn(array('foo.bar'));
        $eventRegistry->expects($this->once())
            ->method('getEventClassName')
            ->with('kernel.request')
            ->willReturn(GetResponseEvent::class);
        $makers['subscriber'] = array(
            new MakeSubscriber($eventRegistry),
            array(
                // subscriber name
                'FooBar',
                // event name
                'kernel.request',
            ),
        );

        $eventRegistry2 = $this->createMock(EventRegistry::class);
        $eventRegistry2->expects($this->any())
            ->method('getAllActiveEvents')
            ->willReturn(array());
        $eventRegistry2->expects($this->once())
            ->method('getEventClassName')
            ->willReturn(null);
        $makers['subscriber_unknown_event_class'] = array(
            new MakeSubscriber($eventRegistry2),
            array(
                // subscriber name
                'FooBar',
                // event name
                'foo.unknown_event',
            ),
        );

        $makers['serializer_encoder'] = array(
            new MakeSerializerEncoder(),
            array(
                // encoder class name
                'FooBarEncoder',
                // encoder format
                'foobar',
            ),
        );

        $makers['twig_extension'] = array(
            new MakeTwigExtension(),
            array(
                // extension class name
                'FooBar',
            ),
        );

        $makers['unit_test'] = array(
            new MakeUnitTest(),
            array(
                // class name
                'FooBar',
            ),
        );

        $makers['validator'] = array(
            new MakeValidator(),
            array(
                // validator name
                'FooBar',
            ),
        );

        $makers['voter'] = array(
            new MakeVoter(),
            array(
                // voter class name
                'FooBar',
            ),
        );

        $makers['auth_empty'] = array(
            new MakeAuthenticator(),
            array(
                // class name
                'AppCustomAuthenticator',
            ),
        );

        return $makers;
    }

    /**
     * Smoke test to make sure the DI autowiring works and all makers
     * are registered and have the correct arguments.
     */
    public function testWiring()
    {
        $kernel = new FunctionalTestKernel('dev', true);

        $finder = new Finder();
        $finder->in(__DIR__.'/../../src/Maker');

        $application = new Application($kernel);
        foreach ($finder as $file) {
            $class = 'Symfony\Bundle\MakerBundle\Maker\\'.substr($file->getFilename(), 0, strlen($file->getFilename()) - 4);

            $commandName = $class::getCommandName();
            // if the command does not exist, this will explode
            $command = $application->find($commandName);
            // just a smoke test assert
            $this->assertInstanceOf(MakerCommand::class, $command);
        }
    }

    private function createGenerator()
    {
        return new Generator(new FileManager(new Filesystem(), $this->targetDir));
    }

    private function parsePHPFiles($output)
    {
        $files = array();
        foreach (explode("\n", $output) as $line) {
            if (false === strpos($line, 'created:')) {
                continue;
            }

            list(, $filename) = explode(':', $line);
            $files[] = trim($filename);
        }

        return $files;
    }
}

class FunctionalTestKernel extends Kernel
{
    use MicroKernelTrait;

    private $cacheDir;

    public function registerBundles()
    {
        return array(
            new FrameworkBundle(),
            new MakerBundle(),
        );
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->setParameter('kernel.secret', 123);
    }

    public function getCacheDir()
    {
        if (null === $this->cacheDir) {
            $this->cacheDir = sys_get_temp_dir().'/'.rand(100, 999);
        }

        return $this->cacheDir;
    }
}
