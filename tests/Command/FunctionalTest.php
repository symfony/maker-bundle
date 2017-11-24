<?php

namespace Symfony\Bundle\MakerBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\MakerBundle\Command\AbstractCommand;
use Symfony\Bundle\MakerBundle\Command\MakeCommandCommand;
use Symfony\Bundle\MakerBundle\Command\MakeControllerCommand;
use Symfony\Bundle\MakerBundle\Command\MakeEntityCommand;
use Symfony\Bundle\MakerBundle\Command\MakeFormCommand;
use Symfony\Bundle\MakerBundle\Command\MakeFunctionalTestCommand;
use Symfony\Bundle\MakerBundle\Command\MakeMigrationCommand;
use Symfony\Bundle\MakerBundle\Command\MakeSubscriberCommand;
use Symfony\Bundle\MakerBundle\Command\MakeTwigExtensionCommand;
use Symfony\Bundle\MakerBundle\Command\MakeUnitTestCommand;
use Symfony\Bundle\MakerBundle\Command\MakeValidatorCommand;
use Symfony\Bundle\MakerBundle\Command\MakeVoterCommand;
use Symfony\Bundle\MakerBundle\EventRegistry;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\RouteCollection;
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
    public function testCommands(AbstractCommand $command, array $inputs)
    {
        /** @var AbstractCommand $command */
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

    public function testMakeMigrationCommand()
    {
        $inputDefinition = $this->createMock(InputDefinition::class);

        $command = new MakeMigrationCommand($this->createGenerator());
        $command->setCheckDependencies(false);
        $command->setDefinition($inputDefinition);
        $command->setGenerator($this->createGenerator());

        $application = $this->createMock(Application::class);
        $application->expects($this->any())
            ->method('find')
            ->willReturn(new Command());

        $application->expects($this->once())
            ->method('getHelperSet')
            ->willReturn(new HelperSet());

        $inputDefinition->expects($this->any())
            ->method('hasArgument')
            ->willReturn(false);


        $inputDefinition->expects($this->once())
            ->method('getOptions')
            ->willReturn([new InputOption('empty')]);
        $option = $this->createMock(InputOption::class);
        $option->expects($this->any())
            ->method('getDefault')
            ->willReturn(false);

        $inputDefinition->expects($this->any())
            ->method('hasOption')
            ->willReturn(true);

        $inputDefinition->expects($this->any())
            ->method('getOption')
            ->willReturn($option);
        $application->expects($this->any())
            ->method('getDefinition')
            ->willReturn($inputDefinition);

        $command->setApplication($application);
        $tester = new CommandTester($command);
        $tester->setInputs(array());
        $tester->execute(array());

        $this->assertContains('Success', $tester->getDisplay());
        $this->assertContains('Version', $tester->getDisplay());

    }

    public function getCommandTests()
    {
        $generator = $this->createGenerator();
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

    private function createGenerator()
    {
        return new Generator(new FileManager(new Filesystem(), $this->targetDir));
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
