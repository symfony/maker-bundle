<?php

namespace Symfony\Bundle\MakerBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Command\AbstractCommand;
use Symfony\Bundle\MakerBundle\Command\MakeCommandCommand;
use Symfony\Bundle\MakerBundle\Command\MakeControllerCommand;
use Symfony\Bundle\MakerBundle\Command\MakeEntityCommand;
use Symfony\Bundle\MakerBundle\Command\MakeFormCommand;
use Symfony\Bundle\MakerBundle\Command\MakeFunctionalTestCommand;
use Symfony\Bundle\MakerBundle\Command\MakeSubscriberCommand;
use Symfony\Bundle\MakerBundle\Command\MakeTwigExtensionCommand;
use Symfony\Bundle\MakerBundle\Command\MakeUnitTestCommand;
use Symfony\Bundle\MakerBundle\Command\MakeValidatorCommand;
use Symfony\Bundle\MakerBundle\Command\MakeVoterCommand;
use Symfony\Bundle\MakerBundle\EventRegistry;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class FunctionalTest extends TestCase
{
    private $fs;
    private $targetDir;

    public function setUp()
    {
        $this->targetDir = sys_get_temp_dir().'/'.uniqid('sf_maker', true);
        $this->fs = new Filesystem();
        $this->fs->mkdir($this->targetDir);
    }

    public function tearDown()
    {
        $this->fs->remove($this->targetDir);
    }

    /**
     * @dataProvider getCommandTests
     */
    public function testCommands(AbstractCommand $command, array $inputs)
    {
        $command->setCheckDependencies(false);
        $command->setGenerator($this->createGenerator());

        $tester = new CommandTester($command);
        $tester->setInputs($inputs);
        $tester->execute([]);

        $this->assertContains('Success', $tester->getDisplay());

        $files = $this->parsePHPFiles($tester->getDisplay());
        foreach ($files as $file) {
            $process = new Process(sprintf('php -l %s', $file), $this->targetDir);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \Exception(sprintf('File "%s" has a syntax error: %s', $file, $process->getOutput()));
            }
        }
    }

    public function getCommandTests()
    {
        // fake generator, the real will be passed in testCommands()
        $generator = $this->createMock(Generator::class);
        $commands = [];

        $commands['command'] = [
            new MakeCommandCommand($generator),
            [
                // command name
                'app:foo'
            ]
        ];

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn(new RouteCollection());
        $commands['controller'] = [
            new MakeControllerCommand($generator, $router),
            [
                // controller class name
                'FooBar'
            ]
        ];

        $commands['entity'] = [
            new MakeEntityCommand($generator),
            [
                // entity class name
                'FooBar'
            ]
        ];

        $commands['form'] = [
            new MakeFormCommand($generator),
            [
                // form name
                'FooBar'
            ]
        ];

        $commands['functional'] = [
            new MakeFunctionalTestCommand($generator),
            [
                // functional test class
                'FooBar'
            ]
        ];

        $eventRegistry = $this->createMock(EventRegistry::class);
        $eventRegistry->expects($this->any())
            ->method('getAllActiveEvents')
            ->willReturn(['foo.bar']);
        $eventRegistry->expects($this->once())
            ->method('getEventClassName')
            ->with('kernel.request')
            ->willReturn(GetResponseEvent::class);
        $commands['subscriber'] = [
            new MakeSubscriberCommand($generator, $eventRegistry),
            [
                // subscriber name
                'FooBar',
                // event name
                'kernel.request'
            ],
        ];

        $eventRegistry2 = $this->createMock(EventRegistry::class);
        $eventRegistry2->expects($this->any())
            ->method('getAllActiveEvents')
            ->willReturn([]);
        $eventRegistry2->expects($this->once())
            ->method('getEventClassName')
            ->willReturn(null);
        $commands['subscriber_unknown_event_class'] = [
            new MakeSubscriberCommand($generator, $eventRegistry2),
            [
                // subscriber name
                'FooBar',
                // event name
                'foo.unknown_event'
            ],
        ];

        $commands['twig_extension'] = [
            new MakeTwigExtensionCommand($generator),
            [
                // extension class name
                'FooBar'
            ]
        ];

        $commands['unit_test'] = [
            new MakeUnitTestCommand($generator),
            [
                // class name
                'FooBar'
            ]
        ];

        $commands['validator'] = [
            new MakeValidatorCommand($generator),
            [
                // validator name
                'FooBar'
            ]
        ];

        $commands['voter'] = [
            new MakeVoterCommand($generator),
            [
                // voter class name
                'FooBar'
            ]
        ];

        return $commands;
    }

    public function testOverrideSkeleton()
    {
        $generator = new Generator(new FileManager(new Filesystem(), __DIR__.'/../Fixtures/src', $this->targetDir));

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('getRouteCollection')
            ->willReturn(new RouteCollection());

        $command = new MakeControllerCommand($generator, $router);
        $command->setCheckDependencies(false);

        $tester = new CommandTester($command);
        $tester->setInputs(['FooAction']);
        $tester->execute([]);

        $this->assertContains('Success', $tester->getDisplay());

        $file = current($this->parsePHPFiles($tester->getDisplay()));
        $process = new Process(sprintf('php -l %s', $file), $this->targetDir);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \Exception(sprintf('File "%s" has a syntax error: %s', $file, $process->getOutput()));
        }

        $this->assertNotContains('AbstractController', file_get_contents($this->targetDir.'/'.$file));
    }

    private function createGenerator()
    {
        return new Generator(new FileManager(new Filesystem(), $this->targetDir, $this->targetDir));
    }

    private function parsePHPFiles($output)
    {
        $files = [];
        foreach (explode("\n", $output) as $line) {
            if (false === strpos($line, 'created:')) {
                continue;
            }

            [, $filename] = explode(':', $line);
            $files[] = trim($filename);
        }

        return $files;
    }
}
