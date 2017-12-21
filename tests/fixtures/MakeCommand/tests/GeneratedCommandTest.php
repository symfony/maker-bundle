<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class GeneratedCommandTest extends KernelTestCase
{
    public function testCommand()
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:foo');

        $tester = new CommandTester($command);
        $tester->execute(array());

        $this->assertContains('You have a new command', $tester->getDisplay());
    }
}
