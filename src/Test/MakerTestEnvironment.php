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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\InputStream;

/**
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 * @author Nicolas Philippe <nikophil@gmail.com>
 *
 * @internal
 */
final class MakerTestEnvironment
{
    // Config used for creating tmp flex project and test app's
    private const GIT_CONFIG = 'git config user.name "symfony" && git config user.email "test@symfony.com" && git config commit.gpgsign false && git config user.signingkey false';

    public const GENERATED_FILES_REGEX = '#(?:created|updated):\s(?:.*\\\\)*(.*\.[a-z]{3,4}).*(?:\\\\n)?#ui';

    private Filesystem $fs;
    private bool|string $rootPath;
    private string $cachePath;
    private string $flexPath;
    private string $path;
    private MakerTestProcess $runnedMakerProcess;
    private bool $isWindows;

    private function __construct(
        private MakerTestDetails $testDetails,
    ) {
        $this->isWindows = str_contains(strtolower(\PHP_OS), 'win');

        $this->fs = new Filesystem();
        $this->rootPath = realpath(__DIR__.'/../../');
        $cachePath = $this->rootPath.'/tests/tmp/cache';

        if (!$this->fs->exists($cachePath)) {
            $this->fs->mkdir($cachePath);
        }

        $this->cachePath = realpath($cachePath);
        $targetVersion = $this->getTargetSkeletonVersion();
        $this->flexPath = $this->cachePath.'/flex_project'.$targetVersion;

        $directoryName = $targetVersion ?: 'current';
        if (str_ends_with($directoryName, '.*')) {
            $directoryName = substr($directoryName, 0, -2);
        }

        $this->path = $this->cachePath.\DIRECTORY_SEPARATOR.$testDetails->getUniqueCacheDirectoryName().'_'.$directoryName;
    }

    public static function create(MakerTestDetails $testDetails): self
    {
        return new self($testDetails);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function readFile(string $path): string
    {
        if (!file_exists($this->path.'/'.$path)) {
            throw new \InvalidArgumentException(\sprintf('Cannot find file "%s"', $path));
        }

        return file_get_contents($this->path.'/'.$path);
    }

    private function changeRootNamespaceIfNeeded(): void
    {
        if ('App' === ($rootNamespace = $this->testDetails->getRootNamespace())) {
            return;
        }

        $replacements = [
            [
                'filename' => 'composer.json',
                'find' => '"App\\\\": "src/"',
                'replace' => '"'.$rootNamespace.'\\\\": "src/"',
            ],
            [
                'filename' => 'src/Kernel.php',
                'find' => 'namespace App',
                'replace' => 'namespace '.$rootNamespace,
            ],
            [
                'filename' => 'bin/console',
                'find' => 'use App\\Kernel',
                'replace' => 'use '.$rootNamespace.'\\Kernel',
            ],
            [
                'filename' => 'public/index.php',
                'find' => 'use App\\Kernel',
                'replace' => 'use '.$rootNamespace.'\\Kernel',
            ],
            [
                'filename' => 'config/services.yaml',
                'find' => 'App\\',
                'replace' => $rootNamespace.'\\',
            ],
            [
                'filename' => '.env.test',
                'find' => 'KERNEL_CLASS=\'App\Kernel\'',
                'replace' => 'KERNEL_CLASS=\''.$rootNamespace.'\Kernel\'',
            ],
        ];

        if ($this->fs->exists($this->path.'/config/packages/doctrine.yaml')) {
            $replacements[] = [
                'filename' => 'config/packages/doctrine.yaml',
                'find' => 'App',
                'replace' => $rootNamespace,
            ];
        }

        $this->processReplacements($replacements, $this->path);
        $this->runCommand('composer dump-autoload');
    }

    public function prepareDirectory(): void
    {
        // Copy MakerBundle to a "repo" directory for tests
        if (!file_exists($makerRepoPath = \sprintf('%s/maker-repo', $this->cachePath))) {
            MakerTestProcess::create(\sprintf('git clone %s %s', $this->rootPath, $makerRepoPath), $this->cachePath)->run();
        }

        if (!$this->fs->exists($this->flexPath)) {
            $this->buildFlexSkeleton();
        }

        if (!$this->fs->exists($this->path)) {
            try {
                // let's do some magic here git is faster than copy
                MakerTestProcess::create(
                    '\\' === \DIRECTORY_SEPARATOR ? 'git clone %FLEX_PATH% %APP_PATH%' : 'git clone "$FLEX_PATH" "$APP_PATH"',
                    \dirname($this->flexPath),
                    [
                        'FLEX_PATH' => $this->flexPath,
                        'APP_PATH' => $this->path,
                    ]
                )
                    ->run();

                // In Window's we have to require MakerBundle in each project - git clone doesn't symlink well
                if ($this->isWindows) {
                    $this->composerRequireMakerBundle($this->path);
                }

                // install any missing dependencies
                $dependencies = $this->determineMissingDependencies();
                if ($dependencies) {
                    // -v actually silences the "very" verbose output in case of an error
                    $composerProcess = MakerTestProcess::create(\sprintf('composer require %s -v', implode(' ', $dependencies)), $this->path)
                        ->run(true)
                    ;

                    if (!$composerProcess->isSuccessful()) {
                        throw new \Exception(\sprintf('Error running command: composer require %s -v. Output: "%s". Error: "%s"', implode(' ', $dependencies), $composerProcess->getOutput(), $composerProcess->getErrorOutput()));
                    }
                }

                $this->changeRootNamespaceIfNeeded();

                file_put_contents($this->path.'/.gitignore', "var/cache/\nvendor/\n");

                MakerTestProcess::create(\sprintf('git diff --quiet || ( %s && git add . && git commit -a -m "second commit" )', self::GIT_CONFIG),
                    $this->path
                )->run();
            } catch (\Exception $e) {
                $this->fs->remove($this->path);

                throw $e;
            }
        } else {
            MakerTestProcess::create('git reset --hard && git clean -fd', $this->path)->run();
            $this->fs->remove($this->path.'/var/cache');
        }
    }

    public function runCommand(string $command): MakerTestProcess
    {
        return MakerTestProcess::create($command, $this->path)->run();
    }

    public function runMaker(array $inputs, string $argumentsString = '', bool $allowedToFail = false, array $envVars = []): MakerTestProcess
    {
        // Let's remove cache
        $this->fs->remove($this->path.'/var/cache');

        $testProcess = $this->createInteractiveCommandProcess(
            commandName: $this->testDetails->getMaker()::getCommandName(),
            userInputs: $inputs,
            argumentsString: $argumentsString,
            envVars: $envVars,
        );

        $this->runnedMakerProcess = $testProcess->run($allowedToFail);

        return $this->runnedMakerProcess;
    }

    public function getGeneratedFilesFromOutputText(): array
    {
        $output = $this->runnedMakerProcess->getOutput();

        $matches = [];

        preg_match_all(self::GENERATED_FILES_REGEX, $output, $matches, \PREG_PATTERN_ORDER);

        return array_map('trim', $matches[1]);
    }

    public function fileExists(string $file): bool
    {
        return $this->fs->exists($this->path.'/'.$file);
    }

    public function runTwigCSLint(string $file): MakerTestProcess
    {
        if (!file_exists(__DIR__.'/../../tools/twigcs/vendor/bin/twigcs')) {
            throw new \Exception('twigcs not found: run: "composer upgrade -W --working-dir=tools/twigcs".');
        }

        return MakerTestProcess::create(\sprintf('php tools/twigcs/vendor/bin/twigcs --config ./tools/twigcs/.twig_cs.dist %s', $this->path.'/'.$file), $this->rootPath)
                               ->run(true);
    }

    private function buildFlexSkeleton(): void
    {
        $targetVersion = $this->getTargetSkeletonVersion();
        $versionString = $targetVersion ? \sprintf(':%s', $targetVersion) : '';

        $flexProjectDir = \sprintf('flex_project%s', $targetVersion);

        MakerTestProcess::create(
            \sprintf('composer create-project symfony/skeleton%s %s --prefer-dist --no-progress', $versionString, $flexProjectDir),
            $this->cachePath
        )->run();

        $rootPath = str_replace('\\', '\\\\', realpath(__DIR__.'/../..'));

        $this->addMakerBundleRepoToComposer(\sprintf('%s/%s/composer.json', $this->cachePath, $flexProjectDir));

        // In Linux, git plays well with symlinks - we can add maker to the flex skeleton.
        if (!$this->isWindows) {
            $this->composerRequireMakerBundle(\sprintf('%s/%s', $this->cachePath, $flexProjectDir));
        }

        if ($_SERVER['MAKER_ALLOW_DEV_DEPS_IN_APP'] ?? false) {
            MakerTestProcess::create('composer config minimum-stability dev', $this->flexPath)->run();
            MakerTestProcess::create('composer config prefer-stable true', $this->flexPath)->run();
        }

        // fetch a few packages needed for testing
        MakerTestProcess::create('composer require phpunit browser-kit symfony/css-selector --prefer-dist --no-progress --no-suggest', $this->flexPath)
                        ->run();

        if ('\\' !== \DIRECTORY_SEPARATOR) {
            $this->fs->remove($this->flexPath.'/vendor/symfony/phpunit-bridge');

            $this->fs->symlink($rootPath.'/vendor/symfony/phpunit-bridge', $this->flexPath.'/vendor/symfony/phpunit-bridge');
        }

        $replacements = [
            // temporarily ignoring indirect deprecations - see #237
            [
                'filename' => '.env.test',
                'find' => 'SYMFONY_DEPRECATIONS_HELPER=999999',
                'replace' => 'SYMFONY_DEPRECATIONS_HELPER=max[self]=0',
            ],
            // do not explicitly set the PHPUnit version
            [
                'filename' => 'phpunit.xml.dist',
                'find' => '<server name="SYMFONY_PHPUNIT_VERSION" value="9.6" />',
                'replace' => '',
            ],
        ];
        $this->processReplacements($replacements, $this->flexPath);
        // end of temp code

        file_put_contents($this->flexPath.'/.gitignore', "var/cache/\n");

        // Force adding vendor/ dir to Git repo in case users exclude it in global .gitignore
        MakerTestProcess::create(\sprintf('git init && %s && git add . && git add vendor/ -f && git commit -a -m "first commit"', self::GIT_CONFIG),
            $this->flexPath
        )->run();
    }

    private function processReplacements(array $replacements, string $rootDir): void
    {
        foreach ($replacements as $replacement) {
            $this->processReplacement($rootDir, $replacement['filename'], $replacement['find'], $replacement['replace']);
        }
    }

    public function processReplacement(string $rootDir, string $filename, string $find, string $replace, bool $allowNotFound = false): void
    {
        $path = realpath($rootDir.'/'.$filename);

        if (!$this->fs->exists($path)) {
            if ($allowNotFound) {
                return;
            }

            throw new \Exception(\sprintf('Could not find file "%s" to process replacements inside "%s"', $filename, $rootDir));
        }

        $contents = file_get_contents($path);
        if (!str_contains($contents, $find)) {
            if ($allowNotFound) {
                return;
            }

            throw new \Exception(\sprintf('Could not find "%s" inside "%s"', $find, $filename));
        }

        file_put_contents($path, str_replace($find, $replace, $contents));
    }

    public function createInteractiveCommandProcess(string $commandName, array $userInputs, string $argumentsString = '', array $envVars = []): MakerTestProcess
    {
        $envVars = array_merge(['SHELL_INTERACTIVE' => '1'], $envVars);

        // We don't need ansi coloring in tests!
        $process = MakerTestProcess::create(
            commandLine: \sprintf('php bin/console %s %s --no-ansi', $commandName, $argumentsString),
            cwd: $this->path,
            envVars: $envVars,
            timeout: 30
        );

        if ($userInputs) {
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

            $process->setInput($inputStream);
        }

        return $process;
    }

    public function getSymfonyVersionInApp(): int
    {
        $contents = file_get_contents($this->getPath().'/vendor/symfony/http-kernel/Kernel.php');
        $position = strpos($contents, 'VERSION_ID = ');

        return (int) substr($contents, $position + 13, 5);
    }

    public function doesClassExistInApp(string $class): bool
    {
        $classMap = require $this->getPath().'/vendor/composer/autoload_classmap.php';

        return isset($classMap[$class]);
    }

    /**
     * Executes the DependencyBuilder for the Maker command inside the
     * actual project, so we know exactly what dependencies we need or
     * don't need.
     */
    private function determineMissingDependencies(): array
    {
        $depBuilder = $this->testDetails->getDependencyBuilder();
        file_put_contents($this->path.'/dep_builder', serialize($depBuilder));
        file_put_contents($this->path.'/dep_runner.php', '<?php

require __DIR__."/vendor/autoload.php";
$depBuilder = unserialize(file_get_contents("dep_builder"));
$missingDependencies = array_merge(
    $depBuilder->getMissingDependencies(),
    $depBuilder->getMissingDevDependencies()
);
echo json_encode($missingDependencies);
        ');

        $process = MakerTestProcess::create('php dep_runner.php', $this->path)->run();
        $data = json_decode($process->getOutput(), true, 512, \JSON_THROW_ON_ERROR);

        unlink($this->path.'/dep_builder');
        unlink($this->path.'/dep_runner.php');

        return array_merge($data, $this->testDetails->getExtraDependencies());
    }

    public function getTargetSkeletonVersion(): ?string
    {
        return $_SERVER['SYMFONY_VERSION'] ?? '';
    }

    private function composerRequireMakerBundle(string $projectDirectory): void
    {
        MakerTestProcess::create('composer require --dev symfony/maker-bundle', $projectDirectory)
            ->run()
        ;

        $makerRepoSrcPath = \sprintf('%s/maker-repo/src', $this->cachePath);

        // DX - So we can test local changes without having to commit them.
        if (!is_link($makerRepoSrcPath)) {
            $this->fs->remove($makerRepoSrcPath);
            $this->fs->symlink(\sprintf('%s/src', $this->rootPath), $makerRepoSrcPath);
        }
    }

    /**
     * Adds Symfony/MakerBundle as a "path" repository to composer.json.
     */
    private function addMakerBundleRepoToComposer(string $composerJsonPath): void
    {
        $composerJson = json_decode(
            file_get_contents($composerJsonPath), true, 512, \JSON_THROW_ON_ERROR);

        // Require-dev is empty and composer complains about this being an array when we encode it again.
        unset($composerJson['require-dev']);

        $composerJson['repositories']['symfony/maker-bundle'] = [
            'type' => 'path',
            'url' => \sprintf('%s%smaker-repo', $this->cachePath, \DIRECTORY_SEPARATOR),
            'options' => [
                'versions' => [
                    'symfony/maker-bundle' => '9999.99', // Arbitrary version to avoid stability conflicts
                ],
            ],
        ];

        file_put_contents($composerJsonPath, json_encode($composerJson, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
    }
}
