<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Test;

use PHPUnit\Framework\TestCase;

class MakerTestCase extends TestCase
{
    protected function executeMakerCommand(MakerTestDetails $testDetails)
    {
        $testEnvironment = MakerTestEnvironment::create($testDetails);

        // prepare environment to test
        $testEnvironment->prepare();

        $makerTestProcess = $testEnvironment->runMaker();
        //  Run tests

        $files = $testEnvironment->getGeneratedFilesFromOutputText();

        foreach ($files as $file) {
            $this->assertTrue($testEnvironment->fileExists($file));

            if ('.php' == substr($file, -4)) {
                $csProcess = $testEnvironment->runPhpCSFixer($file);

                $this->assertTrue($csProcess->isSuccessful(), sprintf('File "%s" has a php-cs problem: %s', $file, $csProcess->getOutput()));
            }

            if ('.twig' == substr($file, -5)) {
                $csProcess = $testEnvironment->runTwigCSLint($file);

                $this->assertTrue($csProcess->isSuccessful(), sprintf('File "%s" has a twig-cs problem: %s', $file, $csProcess->getOutput()));
            }
        }

        //run internal tests
        $internalTestProcess = $testEnvironment->runInternalTests();
        if (null !== $internalTestProcess) {
            $this->assertTrue($internalTestProcess->isSuccessful(), sprintf("Error while running the PHPUnit tests *in* the project: \n\n %s \n\n Command Output: %s", $internalTestProcess->getOutput(), $makerTestProcess->getOutput()));
        }

        //checkout user asserts
        if (null === $testDetails->getAssert()) {
            $this->assertContains('Success', $makerTestProcess->getOutput(), $makerTestProcess->getErrorOutput());
        } else {
            ($testDetails->getAssert())($makerTestProcess->getOutput(), $testEnvironment->getPath());
        }

        // reset envirinment
        $testEnvironment->reset();
    }

    protected function assertContainsCount(string $needle, string $haystack, int $count)
    {
        $this->assertEquals(1, substr_count($haystack, $needle), sprintf('Found more than %d occurrences of "%s" in "%s"', $count, $needle, $haystack));
    }
}
