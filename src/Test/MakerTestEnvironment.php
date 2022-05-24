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
    private Filesystem $fs;
    private bool|string $rootPath;
    private string $cachePath;
    private string $flexPath;
    private string $path;
    private MakerTestProcess $runnedMakerProcess;

    private function __construct(
        private MakerTestDetails $testDetails,
    ) {
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
            throw new \InvalidArgumentException(sprintf('Cannot find file "%s"', $path));
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
        if (!$this->fs->exists($this->flexPath)) {
            $this->buildFlexSkeleton();
        }

        if (!$this->fs->exists($this->path)) {
            try {
                // lets do some magic here git is faster than copy
                MakerTestProcess::create(
                    '\\' === \DIRECTORY_SEPARATOR ? 'git clone %FLEX_PATH% %APP_PATH%' : 'git clone "$FLEX_PATH" "$APP_PATH"',
                    \dirname($this->flexPath),
                    [
                        'FLEX_PATH' => $this->flexPath,
                        'APP_PATH' => $this->path,
                    ]
                )
                    ->run();

                // install any missing dependencies
                $dependencies = $this->determineMissingDependencies();
                if ($dependencies) {
                    MakerTestProcess::create(sprintf('composer require %s', implode(' ', $dependencies)), $this->path)
                        ->run();
                }

                $this->changeRootNamespaceIfNeeded();

                file_put_contents($this->path.'/.gitignore', "var/cache/\nvendor/\n");

                MakerTestProcess::create('git diff --quiet || ( git config user.name "symfony" && git config user.email "test@symfony.com" && git add . && git commit -a -m "second commit" )',
                    $this->path
                )->run();
            } catch (\Exception $e) {
                $this->fs->remove($this->path);

                throw $e;
            }
        } else {
            MakerTestProcess::create('git reset --hard && git clean -fd', $this->path)->run();
        }
    }

    public function runCommand(string $command): MakerTestProcess
    {
        return MakerTestProcess::create($command, $this->path)->run();
    }

    public function runMaker(array $inputs, string $argumentsString = '', bool $allowedToFail = false): MakerTestProcess
    {
        // Let's remove cache
        $this->fs->remove($this->path.'/var/cache');

        $testProcess = $this->createInteractiveCommandProcess(
            $this->testDetails->getMaker()::getCommandName(),
            $inputs,
            $argumentsString
        );

        $this->runnedMakerProcess = $testProcess->run($allowedToFail);

        return $this->runnedMakerProcess;
    }

    public function getGeneratedFilesFromOutputText(): array
    {
        $output = $this->runnedMakerProcess->getOutput();

        $matches = [];

        preg_match_all('#(created|updated): (]8;;[^]*\\\)?(.*?)(]8;;\\\)?\n#iu', $output, $matches, \PREG_PATTERN_ORDER);

        return array_map('trim', $matches[3]);
    }

    public function fileExists(string $file): bool
    {
        return $this->fs->exists($this->path.'/'.$file);
    }

    public function runPhpCSFixer(string $file): MakerTestProcess
    {
        if (!file_exists(__DIR__.'/../../tools/php-cs-fixer/vendor/bin/php-cs-fixer')) {
            throw new \Exception('php-cs-fixer not found: run: "composer install --working-dir=tools/php-cs-fixer".');
        }

        return MakerTestProcess::create(
            sprintf('php tools/php-cs-fixer/vendor/bin/php-cs-fixer --config=%s fix --dry-run --diff %s', __DIR__.'/../Resources/test/.php_cs.test', $this->path.'/'.$file),
            $this->rootPath,
            ['PHP_CS_FIXER_IGNORE_ENV' => '1']
        )->run(true);
    }

    public function runTwigCSLint(string $file): MakerTestProcess
    {
        if (!file_exists(__DIR__.'/../../tools/twigcs/vendor/bin/twigcs')) {
            throw new \Exception('twigcs not found: run: "composer install --working-dir=tools/twigcs".');
        }

        return MakerTestProcess::create(sprintf('php tools/twigcs/vendor/bin/twigcs --config ./tools/twigcs/.twig_cs.dist %s', $this->path.'/'.$file), $this->rootPath)
                               ->run(true);
    }

    private function buildFlexSkeleton(): void
    {
        $targetVersion = $this->getTargetSkeletonVersion();
        $versionString = $targetVersion ? sprintf(':%s', $targetVersion) : '';

        MakerTestProcess::create(
            sprintf('composer create-project symfony/skeleton%s flex_project%s --prefer-dist --no-progress', $versionString, $targetVersion),
            $this->cachePath
        )->run();

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
                'find' => '<server name="SYMFONY_PHPUNIT_VERSION" value="9.5" />',
                'replace' => '',
            ],
        ];
        $this->processReplacements($replacements, $this->flexPath);
        // end of temp code

        file_put_contents($this->flexPath.'/.gitignore', "var/cache/\n");

        // Force adding vendor/ dir to Git repo in case users exclude it in global .gitignore
        MakerTestProcess::create('git init && git config user.name "symfony" && git config user.email "test@symfony.com" && git add . && git add vendor/ -f && git commit -a -m "first commit"',
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

            throw new \Exception(sprintf('Could not find file "%s" to process replacements inside "%s"', $filename, $rootDir));
        }

        $contents = file_get_contents($path);
        if (!str_contains($contents, $find)) {
            if ($allowNotFound) {
                return;
            }

            throw new \Exception(sprintf('Could not find "%s" inside "%s"', $find, $filename));
        }

        file_put_contents($path, str_replace($find, $replace, $contents));
    }

    public function createInteractiveCommandProcess(string $commandName, array $userInputs, string $argumentsString = ''): MakerTestProcess
    {
        // We don't need ansi coloring in tests!
        $process = MakerTestProcess::create(
            sprintf('php bin/console %s %s --no-ansi', $commandName, $argumentsString),
            $this->path,
            [
                'SHELL_INTERACTIVE' => '1',
            ],
            10
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

    private function getTargetSkeletonVersion(): ?string
    {
        return $_SERVER['SYMFONY_VERSION'] ?? '';
    }
}
