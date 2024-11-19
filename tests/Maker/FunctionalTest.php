<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\MakerBundle\Command\MakerCommand;
use Symfony\Bundle\MakerBundle\Test\MakerTestKernel;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Finder\Finder;

class FunctionalTest extends TestCase
{
    /**
     * Smoke test to make sure the DI autowiring works and all makers
     * are registered and have the correct arguments.
     */
    public function testWiring(): void
    {
        $kernel = new MakerTestKernel('dev', true);

        $finder = new Finder();
        $finder
            ->in(__DIR__.'/../../src/Maker')
            // exclude deprecated classes
            ->notContains('/@deprecated/')
            // exclude Maker/Common/ as no maker's should live in this dir
            ->notPath('Common')
        ;

        $application = new Application($kernel);
        foreach ($finder as $file) {
            $classNameFromPath = str_replace(
                ['/', '.php'], // We need to flip and "/" to "\" and remove ".php"
                ['\\', ''],
                \sprintf('Symfony\Bundle\MakerBundle\Maker\%s', $file->getRelativePathname())
            );

            $maker = new \ReflectionClass($classNameFromPath);

            if ($maker->isAbstract()) {
                continue;
            }

            // if the command does not exist, this will explode
            $command = $application->find(
                $maker->getMethod('getCommandName')->invoke(null)
            );

            if ($command instanceof LazyCommand) {
                $command = $command->getCommand();
            }

            // just a smoke test assert
            self::assertInstanceOf(MakerCommand::class, $command);
        }
    }
}
