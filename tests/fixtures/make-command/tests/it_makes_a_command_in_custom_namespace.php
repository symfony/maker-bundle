<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Tester\CommandTester;

class GeneratedCommandTest extends KernelTestCase
{
    public function testCommand()
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:foo');
        if ($command instanceof LazyCommand) {
            $command = $command->getCommand();
        }

        $this->assertStringStartsWith('Custom\\', \get_class($command));

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertStringContainsString('You have a new command', $tester->getDisplay());
    }
}
