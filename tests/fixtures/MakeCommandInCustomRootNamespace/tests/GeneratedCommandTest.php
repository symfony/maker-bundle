<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

        $this->assertStringStartsWith('Custom\\', \get_class($command));

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertStringContainsString('You have a new command', $tester->getDisplay());
    }
}
