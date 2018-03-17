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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class MakerTestCase extends TestCase
{
    protected static $currentRootDir;
    private static $flexProjectPath;
    private static $fixturesCachePath;

    /** @var Filesystem */
    private static $fs;

    /**
     * @beforeClass
     */
    public static function setupPaths()
    {
        self::$currentRootDir = __DIR__.'/../../tests/tmp/current_project';
        self::$fixturesCachePath = __DIR__.'/../../tests/tmp/cache';
        self::$flexProjectPath = self::$fixturesCachePath.'/flex_project';
    }

    protected function executeMakerCommand(MakerTestDetails $testDetails)
    {
        self::$fs = new Filesystem();

        if (!file_exists(self::$fixturesCachePath)) {
            self::$fs->mkdir(self::$fixturesCachePath);
        }

        if (!file_exists(self::$flexProjectPath)) {
            $this->buildFlexProject();
        }

        // puts the project into self::$currentRootDir
        $this->prepareProjectDirectory($testDetails);

        foreach ($testDetails->getPreMakeCommands() as $preCommand) {
            $process = $this->createProcess($preCommand, self::$currentRootDir);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \Exception(sprintf('Error with pre command: "%s": "%s" "%s"', $preCommand, $process->getOutput(), $process->getErrorOutput()));
            }
        }

        $executableFinder = new PhpExecutableFinder();
        $phpPath = $executableFinder->find(false);
        $makerProcess = $this->createProcess(
            sprintf('%s bin/console %s %s', $phpPath, ($testDetails->getMaker())::getCommandName(), $testDetails->getArgumentsString()),
            self::$currentRootDir
        );
        $makerProcess->setTimeout(10);

        // tells the command we are in interactive mode
        $makerProcess->setEnv([
            'SHELL_INTERACTIVE' => '1',
        ]);

        if ($userInputs = $testDetails->getInputs()) {
            $inputStream = new InputStream();

            // start the command with some input
            $inputStream->write(current($userInputs)."\n");

            $inputStream->onEmpty(function () use ($inputStream, &$userInputs) {
                $nextInput = next($userInputs);
                if (false === $nextInput) {
                    $inputStream->close();
                } else {
                    $inputStream->write($nextInput."\n");
                }
            });
            $makerProcess->setInput($inputStream);
        }

        $makerProcess->run();

        if (!$makerProcess->isSuccessful() && !$testDetails->isCommandAllowedToFail()) {
            throw new \Exception(sprintf('Running maker command failed: "%s" "%s"', $makerProcess->getOutput(), $makerProcess->getErrorOutput()));
        }

        $files = $this->getGeneratedFilesFromOutputText($makerProcess->getOutput(), '.php');
        foreach ($files as $file) {
            $process = $this->createProcess(
                sprintf(
                    'php vendor/bin/php-cs-fixer --config=%s fix --dry-run --diff %s',
                    __DIR__.'/../Resources/test/.php_cs.test',
                    self::$currentRootDir.'/'.$file
                ),
                __DIR__.'/../../'
            );
            $process->run();
            $this->assertTrue($process->isSuccessful(), sprintf('File "%s" has a php-cs problem: %s', $file, $process->getOutput()));
        }

        $files = $this->getGeneratedFilesFromOutputText($makerProcess->getOutput(), '.twig');
        foreach ($files as $file) {
            $process = $this->createProcess(
                sprintf(
                    'php vendor/bin/twigcs lint %s',
                    self::$currentRootDir.'/'.$file
                ),
                __DIR__.'/../../'
            );
            $process->run();
            $this->assertTrue($process->isSuccessful(), sprintf('File "%s" has a php-cs problem: %s', $file, $process->getOutput()));
        }

        foreach ($testDetails->getPostMakeCommands() as $postCommand) {
            $process = $this->createProcess($postCommand, self::$currentRootDir);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \Exception(sprintf('Error with post command: "%s": "%s" "%s"', $postCommand, $process->getOutput(), $process->getErrorOutput()));
            }
        }

        $finder = new Finder();
        $finder->in(self::$currentRootDir.'/tests')->files();
        if ($finder->count() > 0) {
            // execute the tests that were moved into the project!
            $process = $this->createProcess(
                // using OUR simple-phpunit for speed (to avoid downloading more deps)
                __DIR__.'/../../vendor/bin/simple-phpunit',
                self::$currentRootDir
            );
            $process->run();

            $this->assertTrue($process->isSuccessful(), sprintf("Error while running the PHPUnit tests *in* the project: \n\n %s \n\n Command Output: %s", $process->getOutput(), $makerProcess->getOutput()));
        }

        if (null === $testDetails->getAssert()) {
            // a generic assert
            $this->assertContains('Success', $makerProcess->getOutput(), $makerProcess->getErrorOutput());
        } else {
            ($testDetails->getAssert())($makerProcess->getOutput(), self::$currentRootDir);
        }
    }

    protected function assertContainsCount(string $needle, string $haystack, int $count)
    {
        $this->assertEquals(1, substr_count($haystack, $needle), sprintf('Found more than %d occurrences of "%s" in "%s"', $count, $needle, $haystack));
    }

    private function buildFlexProject()
    {
        $targetFlexDir = dirname(self::$flexProjectPath);
        if (!file_exists($targetFlexDir)) {
            self::$fs->mkdir($targetFlexDir);
        }
        $process = $this->createProcess('composer create-project symfony/skeleton flex_project', $targetFlexDir);
        $this->runProcess($process);

        // processes any changes needed to the Flex project
        $replacements = [
            [
                'filename' => 'config/bundles.php',
                'find' => "Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],",
                'replace' => "Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],\n    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],",
            ],
            [
                // ugly way to autoload Maker & any other vendor libs needed in the command
                'filename' => 'composer.json',
                'find' => '"App\\\Tests\\\": "tests/"',
                'replace' => sprintf(
                    '"App\\\Tests\\\": "tests/",'."\n".'            "Symfony\\\Bundle\\\MakerBundle\\\": "%s/src/",'."\n".'            "PhpParser\\\": "%s/vendor/nikic/php-parser/lib/PhpParser"',
                    // escape \ for Windows
                    str_replace('\\', '\\\\', __DIR__.'../../..'),
                    str_replace('\\', '\\\\', __DIR__.'../../..')
                ),
            ],
        ];
        $this->processReplacements($replacements, self::$flexProjectPath);

        // fetch a few packages needed for testing
        $process = $this->createProcess('composer require phpunit browser-kit', self::$flexProjectPath);
        $this->runProcess($process);
    }

    private function getGeneratedFilesFromOutputText($output, $fileExtension)
    {
        $files = [];
        foreach (explode("\n", $output) as $line) {
            if (false === strpos($line, 'created:') && false === strpos($line, 'updated:')) {
                continue;
            }

            list(, $filename) = explode(':', $line);
            $filename = trim($filename);
            if ($fileExtension === substr($filename, (-1 * strlen($fileExtension)))) {
                $files[] = trim($filename);
            }
        }

        return $files;
    }

    private function runProcess(Process $process)
    {
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception(sprintf(
                'Error running command: "%s". Output: "%s". Error: "%s"',
                $process->getCommandLine(),
                $process->getOutput(),
                $process->getErrorOutput()
            ));
        }
    }

    private function createProcess($commandLine, $cwd)
    {
        $process = new Process($commandLine, $cwd);
        // avoid 3.x deprecation warnings
        $process->inheritEnvironmentVariables();

        return $process;
    }

    private function prepareProjectDirectory(MakerTestDetails $makerTestDetails)
    {
        if (null !== $makerTestDetails->getFixtureFilesPath() && !file_exists($makerTestDetails->getFixtureFilesPath())) {
            throw new \Exception(sprintf('Cannot find fixtures directory "%s"', $makerTestDetails->getFixtureFilesPath()));
        }

        // initialize the app corresponding to *this* fixtures directory
        // and put it in a cache file so any re-runs are much faster
        $fixturesCacheDir = self::$fixturesCachePath.'/'.$makerTestDetails->getUniqueCacheDirectoryName();
        if (!file_exists($fixturesCacheDir)) {
            try {
                self::$fs->mirror(self::$flexProjectPath, $fixturesCacheDir);

                // install any missing dependencies
                if ($dependencies = $makerTestDetails->getDependencies()) {
                    $process = $this->createProcess(sprintf('composer require %s', implode(' ', $dependencies)), $fixturesCacheDir);
                    $this->runProcess($process);
                }
            } catch (ProcessFailedException $e) {
                self::$fs->remove($fixturesCacheDir);

                throw $e;
            }
        }

        self::$fs->remove(self::$currentRootDir);
        self::$fs->mirror($fixturesCacheDir, self::$currentRootDir);

        // re-dump the autoloader so that it's correct for the new directory
        // this is due the directory being moved and Composer storing the
        // path internally in a relative way
        $process = $this->createProcess('composer dump-autoload', self::$currentRootDir);
        $this->runProcess($process);

        if (null !== $makerTestDetails->getFixtureFilesPath()) {
            // move fixture files into directory
            $finder = new Finder();
            $finder->in($makerTestDetails->getFixtureFilesPath())->files();

            foreach ($finder as $file) {
                if ($file->getPath() === $makerTestDetails->getFixtureFilesPath()) {
                    continue;
                }

                self::$fs->copy($file->getPathname(), self::$currentRootDir.'/'.$file->getRelativePathname(), true);
            }
        }

        if ($makerTestDetails->getReplacements()) {
            $this->processReplacements($makerTestDetails->getReplacements(), self::$currentRootDir);
        }
    }

    private function processReplacements(array $replacements, $rootDir)
    {
        foreach ($replacements as $replacement) {
            $path = $rootDir.'/'.$replacement['filename'];
            $contents = file_get_contents($path);
            if (false === strpos($contents, $replacement['find'])) {
                throw new \Exception(sprintf('Could not find "%s" inside "%s"', $replacement['find'], $replacement['filename']));
            }

            file_put_contents($path, str_replace($replacement['find'], $replacement['replace'], $contents));
        }
    }
}
