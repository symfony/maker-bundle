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

class FunctionalTest extends TestCase
{
    private $targetDir;

    public function setUp()
    {
        $tmpDir = sys_get_temp_dir().'/sf'.mt_rand(111111, 999999);
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
        $tester->execute(array());

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
        $commands = [];

        $commands['command'] = [
            new MakeCommandCommand($this->createGenerator()),
            [
                // command name
                'app:foo'
            ]
        ];

        $commands['controller'] = [
            new MakeControllerCommand($this->createGenerator()),
            [
                // controller class name
                'FooBar'
            ]
        ];

        $commands['entity'] = [
            new MakeEntityCommand($this->createGenerator()),
            [
                // entity class name
                'FooBar'
            ]
        ];

        $commands['form'] = [
            new MakeFormCommand($this->createGenerator()),
            [
                // form name
                'FooBar'
            ]
        ];

        $commands['functional'] = [
            new MakeFunctionalTestCommand($this->createGenerator()),
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
            new MakeSubscriberCommand($this->createGenerator(), $eventRegistry),
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
            new MakeSubscriberCommand($this->createGenerator(), $eventRegistry2),
            [
                // subscriber name
                'FooBar',
                // event name
                'foo.unknown_event'
            ],
        ];

        $commands['twig_extension'] = [
            new MakeTwigExtensionCommand($this->createGenerator()),
            [
                // extension class name
                'FooBar'
            ]
        ];

        $commands['unit_test'] = [
            new MakeUnitTestCommand($this->createGenerator()),
            [
                // class name
                'FooBar'
            ]
        ];

        $commands['validator'] = [
            new MakeValidatorCommand($this->createGenerator()),
            [
                // validator name
                'FooBar'
            ]
        ];

        $commands['voter'] = [
            new MakeVoterCommand($this->createGenerator()),
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

            list(, $filename) = explode(':', $line);
            $files[] = trim($filename);
        }

        return $files;
    }
}
