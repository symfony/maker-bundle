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

use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\InputStream;

/**
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 *
 * @internal
 */
final class MakerTestEnvironment
{
    private $testDetails;

    private $fs;

    private $rootPath;
    private $cachePath;
    private $flexPath;

    private $path;

    private $snapshotFile;

    /**
     * @var MakerTestProcess
     */
    private $runnedMakerProcess;

    private function __construct(MakerTestDetails $testDetails)
    {
        $this->testDetails = $testDetails;
        $this->fs = new Filesystem();

        $this->rootPath = realpath(__DIR__.'/../../');

        $cachePath = $this->rootPath.'/tests/tmp/cache';

        if (!$this->fs->exists($cachePath)) {
            $this->fs->mkdir($cachePath);
        }

        $this->cachePath = realpath($cachePath);
        $this->flexPath = $this->cachePath.'/flex_project';

        $this->path = $this->cachePath.\DIRECTORY_SEPARATOR.$testDetails->getUniqueCacheDirectoryName();

        $this->snapshotFile = $this->path.\DIRECTORY_SEPARATOR.basename($this->path).'.json';
    }

    public static function create(MakerTestDetails $testDetails): self
    {
        return new self($testDetails);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function prepare()
    {
        if (!$this->fs->exists($this->flexPath)) {
            $this->buildFlexSkeleton();
        }

        if (!$this->fs->exists($this->path)) {
            try {
                $this->fs->mirror($this->flexPath, $this->path);

                // install any missing dependencies
                if ($dependencies = $this->testDetails->getDependencies()) {
                    MakerTestProcess::create(sprintf('composer require %s', implode(' ', $dependencies)), $this->path)
                        ->run();
                }

                $this->createSnapshot(true);
            } catch (ProcessFailedException $e) {
                $this->fs->remove($this->path);

                throw $e;
            }
        }

        MakerTestProcess::create('composer dump-autoload', $this->path)
            ->run();

        if (null !== $this->testDetails->getFixtureFilesPath()) {
            // move fixture files into directory
            $finder = new Finder();
            $finder->in($this->testDetails->getFixtureFilesPath())->files();

            foreach ($finder as $file) {
                if ($file->getPath() === $this->testDetails->getFixtureFilesPath()) {
                    continue;
                }

                $this->fs->copy($file->getPathname(), $this->path.'/'.$file->getRelativePathname(), true);
            }
        }

        $this->processReplacements($this->testDetails->getReplacements(), $this->path);

        if ($ignoredFiles = $this->testDetails->getFilesToDelete()) {
            foreach ($ignoredFiles as $file) {
                if (file_exists($this->path.'/'.$file)) {
                    $this->fs->rename($this->path.'/'.$file, $this->path.'/'.$file.'.deleted');
                }
            }
        }

        foreach ($this->testDetails->getFilesToRevert() as $file) {
            if (!file_exists($this->path.'/'.$file)) {
                throw new \Exception(sprintf('Cannot find "%s"', $file));
            }

            $this->fs->copy($this->path.'/'.$file, $this->path.'/'.$file.'.original');
        }
    }

    private function preMake()
    {
        foreach ($this->testDetails->getPreMakeCommands() as $preCommand) {
            MakerTestProcess::create($preCommand, $this->path)
                            ->run();
        }
    }

    public function runMaker()
    {
        $this->preMake();

        MakerTestProcess::create('php bin/console cache:clear --no-ansi', $this->path)
                        ->run();

        // We don't need ansi coloring in tests!
        $testProcess = MakerTestProcess::create(
            sprintf('php bin/console %s %s --no-ansi', $this->testDetails->getMaker()::getCommandName(), $this->testDetails->getArgumentsString()),
            $this->path,
            10
        );

        $testProcess->setEnv([
            'SHELL_INTERACTIVE' => '1',
        ]);

        if ($userInputs = $this->testDetails->getInputs()) {
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

            $testProcess->setInput($inputStream);
        }

        $this->runnedMakerProcess = $testProcess->run($this->testDetails->isCommandAllowedToFail());

        $this->postMake();

        return $this->runnedMakerProcess;
    }

    public function getGeneratedFilesFromOutputText()
    {
        $output = $this->runnedMakerProcess->getOutput();

        $matches = [];

        preg_match_all('#(created|updated): (.*)\n#iu', $output, $matches, PREG_PATTERN_ORDER);

        return array_map('trim', $matches[2]);
    }

    public function fileExists(string $file)
    {
        return $this->fs->exists($this->path.'/'.$file);
    }

    public function runPhpCSFixer(string $file)
    {
        return MakerTestProcess::create(sprintf('php vendor/bin/php-cs-fixer --config=%s fix --dry-run --diff %s', __DIR__.'/../Resources/test/.php_cs.test', $this->path.'/'.$file), $this->rootPath)
                               ->run(true);
    }

    public function runTwigCSLint(string $file)
    {
        return MakerTestProcess::create(sprintf('php vendor/bin/twigcs lint %s', $this->path.'/'.$file), $this->rootPath)
                               ->run(true);
    }

    public function runInternalTests()
    {
        MakerTestProcess::create('php bin/console cache:clear', $this->path)
                        ->run();

        $finder = new Finder();
        $finder->in($this->path.'/tests')->files();
        if ($finder->count() > 0) {
            // execute the tests that were moved into the project!
            return MakerTestProcess::create(sprintf('php %s', $this->rootPath.'/vendor/bin/simple-phpunit'), $this->path)
                                   ->run(true);
        }

        return null;
    }

    private function postMake()
    {
        $this->processReplacements($this->testDetails->getPostMakeReplacements(), $this->path);

        $guardAuthenticators = $this->testDetails->getGuardAuthenticators();
        if (!empty($guardAuthenticators)) {
            $yaml = file_get_contents($this->path.'/config/packages/security.yaml');
            $manipulator = new YamlSourceManipulator($yaml);
            $data = $manipulator->getData();
            foreach ($guardAuthenticators as $firewallName => $id) {
                if (!isset($data['security']['firewalls'][$firewallName])) {
                    throw new \Exception(sprintf('Could not find firewall "%s"', $firewallName));
                }

                $data['security']['firewalls'][$firewallName]['guard'] = [
                    'authenticators' => [$id],
                ];
            }
            $manipulator->setData($data);
            file_put_contents($this->path.'/config/packages/security.yaml', $manipulator->getContents());
        }

        foreach ($this->testDetails->getPostMakeCommands() as $postCommand) {
            MakerTestProcess::create($postCommand, $this->path)
                            ->run();
        }
    }

    public function reset()
    {
        foreach ($this->testDetails->getFilesToRevert() as $file) {
            if (!file_exists($this->path.'/'.$file.'.original')) {
                throw new \Exception(sprintf('Cannot find original file for "%s"', $file));
            }

            $this->fs->rename($this->path.'/'.$file.'.original', $this->path.'/'.$file, true);
        }

        $cleanSnapshot = json_decode(file_get_contents($this->snapshotFile));
        $currentSnapshot = $this->createSnapshot();

        $diff = array_diff($currentSnapshot, $cleanSnapshot);

        if (\count($diff)) {
            $this->fs->remove($diff);
        }

        if ($ignoredFiles = $this->testDetails->getFilesToDelete()) {
            foreach ($ignoredFiles as $file) {
                if (file_exists($this->path.'/'.$file.'.deleted')) {
                    $this->fs->rename($this->path.'/'.$file.'.deleted', $this->path.'/'.$file);
                }
            }
        }

        // no need to revert post make replacements: if something was replaced
        // "post make", then it was a generated file, which will be deleted anyways
        $this->revertReplacements($this->testDetails->getReplacements(), $this->path);
    }

    private function createSnapshot($save = false): array
    {
        $snapshot = [];
        $finder = new Finder();
        $finder->files()->in($this->path)->exclude(['vendor', 'var/cache', 'var/log']);
        if ($finder->count() > 0) {
            foreach ($finder as $file) {
                $snapshot[] = $file->getPathname();
            }
            $snapshot[] = $this->snapshotFile;

            if ($save) {
                $this->fs->dumpFile($this->snapshotFile, json_encode($snapshot));
            }
        }

        return $snapshot;
    }

    private function buildFlexSkeleton()
    {
        MakerTestProcess::create('composer create-project symfony/skeleton flex_project', $this->cachePath)
                        ->run();

        $rootPath = str_replace('\\', '\\\\', realpath(__DIR__.'/../..'));

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
                    '"App\\\Tests\\\": "tests/",'."\n".'            "Symfony\\\Bundle\\\MakerBundle\\\": "%s/src/",'."\n".'            "PhpParser\\\": "%s/vendor/nikic/php-parser/lib/PhpParser/"',
                    // escape \ for Windows
                    $rootPath,
                    $rootPath
                ),
            ],
        ];
        $this->processReplacements($replacements, $this->flexPath);

        // fetch a few packages needed for testing
        MakerTestProcess::create('composer require phpunit browser-kit symfony/css-selector', $this->flexPath)
                        ->run();

        MakerTestProcess::create('php bin/console cache:clear --no-warmup', $this->flexPath)
                        ->run();
    }

    private function revertReplacements(array $replacements, $rootDir)
    {
        foreach ($replacements as $replacement) {
            $path = $rootDir.'/'.$replacement['filename'];
            $contents = file_get_contents($path);
            if (false === strpos($contents, $replacement['replace'])) {
                continue;
            }

            file_put_contents($path, str_replace($replacement['replace'], $replacement['find'], $contents));
        }
    }

    private function processReplacements(array $replacements, $rootDir)
    {
        foreach ($replacements as $replacement) {
            $path = realpath($rootDir.'/'.$replacement['filename']);

            if (!$this->fs->exists($path)) {
                throw new \Exception(sprintf('Could not find file "%s" to process replacements inside "%s"', $replacement['filename'], $rootDir));
            }

            $contents = file_get_contents($path);
            if (false === strpos($contents, $replacement['find'])) {
                throw new \Exception(sprintf('Could not find "%s" inside "%s"', $replacement['find'], $replacement['filename']));
            }

            file_put_contents($path, str_replace($replacement['find'], $replacement['replace'], $contents));
        }
    }
}
