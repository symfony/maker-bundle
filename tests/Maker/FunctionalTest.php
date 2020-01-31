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
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Maker\MakeForgottenPassword;
use Symfony\Bundle\MakerBundle\Test\MakerTestKernel;
use Symfony\Component\Finder\Finder;

class FunctionalTest extends TestCase
{
    /**
     * Smoke test to make sure the DI autowiring works and all makers
     * are registered and have the correct arguments.
     */
    public function testWiring()
    {
        $kernel = new MakerTestKernel('dev', true);

        $finder = new Finder();
        $finder->in(__DIR__.'/../../src/Maker');

        $application = new Application($kernel);
        foreach ($finder as $file) {
            $class = 'Symfony\Bundle\MakerBundle\Maker\\'.$file->getBasename('.php');

            if (AbstractMaker::class === $class) {
                continue;
            }

            /** Skip make forgotten password as it is temp. disabled (tkt#537) */
            if (MakeForgottenPassword::class === $class) {
                continue;
            }

            $commandName = $class::getCommandName();
            // if the command does not exist, this will explode
            $command = $application->find($commandName);
            // just a smoke test assert
            $this->assertInstanceOf(MakerCommand::class, $command);
        }
    }
}
