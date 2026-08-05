<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Application;

use Symfony\Bundle\MakerBundle\Tests\Harness\Assertion\FileAssertions;
use Symfony\Bundle\MakerBundle\Tests\Harness\Fixture\FixtureLocator;
use Symfony\Bundle\MakerBundle\Tests\Harness\Lint\TwigCsLinter;
use Symfony\Bundle\MakerBundle\Tests\Harness\Process\CommandResult;
use Symfony\Bundle\MakerBundle\Tests\Harness\Process\InteractiveCommand;
use Symfony\Bundle\MakerBundle\Tests\Harness\Process\ProcessRunner;
use Symfony\Bundle\MakerBundle\Tests\Harness\Process\Transcript;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * The ONLY test-facing API for the generated application. Every mutation
 * funnels through here so cache invalidation is explicit:
 *
 *  - writes under config/, .env*, src/ (and every maker run) mark the dev
 *    container dirty; the dirty cache is DELETED lazily before the next dev
 *    command — the pristine baseline is never restored mid-test (see AppPool)
 *  - raw composer.json writes are prohibited; dumpAutoload() is the only
 *    vendor-mutating door and implicitly behaves like #[DirtiesVendor]
 */
final class GeneratedApp
{
    private ExecutionProof $proof;
    private FixtureLocator $fixtures;
    private Filesystem $fs;
    private bool $devCacheDirty = false;

    public function __construct(
        private string $appDir,
        private string $profileName,
        private string $makerCommandName,
    ) {
        $this->proof = new ExecutionProof();
        $this->fixtures = new FixtureLocator($makerCommandName);
        $this->fs = new Filesystem();
    }

    public function getPath(string $relativePath = ''): string
    {
        return '' === $relativePath ? $this->appDir : $this->appDir.'/'.$relativePath;
    }

    public function proof(): ExecutionProof
    {
        return $this->proof;
    }

    // ---------------------------------------------------------------- files

    public function fileExists(string $relativePath): bool
    {
        return file_exists($this->getPath($relativePath));
    }

    public function readFile(string $relativePath): string
    {
        if (!$this->fileExists($relativePath)) {
            throw new \InvalidArgumentException(\sprintf('File "%s" does not exist in the app.', $relativePath));
        }

        return file_get_contents($this->getPath($relativePath));
    }

    public function writeFile(string $relativePath, string $contents): void
    {
        $this->guardMutation($relativePath);
        $this->fs->mkdir(\dirname($this->getPath($relativePath)));
        file_put_contents($this->getPath($relativePath), $contents);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function writeYaml(string $relativePath, array $data): void
    {
        $this->writeFile($relativePath, Yaml::dump($data, 4));
    }

    /**
     * @return array<string, mixed>
     */
    public function readYaml(string $relativePath): array
    {
        return (array) Yaml::parse($this->readFile($relativePath));
    }

    public function replaceInFile(string $relativePath, string $find, string $replace): void
    {
        $contents = $this->readFile($relativePath);
        if (!str_contains($contents, $find)) {
            throw new \RuntimeException(\sprintf('Could not find "%s" inside "%s".', $find, $relativePath));
        }

        $this->writeFile($relativePath, str_replace($find, $replace, $contents));
    }

    public function deleteFile(string $relativePath): void
    {
        $this->guardMutation($relativePath);
        $this->fs->remove($this->getPath($relativePath));
    }

    /**
     * Copies a fixture file or directory into the app. Fixture paths resolve
     * against tests/fixtures/<maker-dir>/.
     */
    public function copyFixture(string $fixturePath, string $appPath): void
    {
        $source = $this->fixtures->path($fixturePath);
        $target = $this->getPath($appPath);
        $this->guardMutation($appPath);

        if (is_dir($source)) {
            // override: fixture files must win even when the app's copy has a
            // newer mtime (profile builds are always newer than the checkout)
            $this->fs->mirror($source, $target, options: ['override' => true]);
            // a directory copy can touch config/ and src/ anywhere
            $this->devCacheDirty = true;

            return;
        }

        $this->fs->copy($source, $target, true);
    }

    /**
     * Renders a Twig fixture template into the app (the make-entity generated
     * tests are parameterized this way).
     *
     * @param array<string, mixed> $variables
     */
    public function renderFixtureTemplate(string $fixturePath, string $appPath, array $variables): void
    {
        $twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader($this->fixtures->dir()));

        $this->guardMutation($appPath);
        $this->fs->mkdir(\dirname($this->getPath($appPath)));
        file_put_contents($this->getPath($appPath), $twig->render($fixturePath, $variables));
    }

    // ---------------------------------------------------------------- maker

    /**
     * Fluent maker invocation; call ->run() to get a CommandResult.
     * The maker runs against the warm dev container (APP_DEBUG=0).
     */
    public function runMaker(): InteractiveCommand
    {
        $before = [];

        return new InteractiveCommand(
            \sprintf('php bin/console %s --no-ansi', $this->makerCommandName),
            $this->appDir,
            ['APP_ENV' => 'dev', 'APP_DEBUG' => '0'],
            finisher: function (string $output, int $exitCode, Transcript $transcript) use (&$before): CommandResult {
                $diff = ArtifactManifest::diff($before, ArtifactManifest::snapshot($this->appDir));
                $this->devCacheDirty = true;

                if (0 === $exitCode) {
                    $this->proof->registerArtifacts([...$diff['created'], ...$diff['updated']]);
                }

                return new CommandResult($output, $exitCode, $diff['created'], $diff['updated'], $transcript);
            },
            beforeRun: function () use (&$before): void {
                $this->clearDevCacheIfDirty();
                $before = ArtifactManifest::snapshot($this->appDir);
            },
        );
    }

    /**
     * Runs another console command in the app (e.g. another maker as setup, or
     * doctrine commands). Boots observe maker-generated config for the booted
     * env, per the execution contract.
     *
     * @param array<string> $inputs prompt fragment => answer; string keys
     *                              are enforced at runtime
     */
    public function runConsole(string $command, array $inputs = [], string $env = 'dev'): string
    {
        if ('dev' === $env) {
            $this->clearDevCacheIfDirty();
        }

        $interactive = new InteractiveCommand(
            \sprintf('php bin/console %s --env=%s --no-ansi', $command, $env),
            $this->appDir,
            'dev' === $env ? ['APP_ENV' => 'dev', 'APP_DEBUG' => '0'] : [],
            finisher: function (string $output) use ($env, $command): string {
                if (str_starts_with($command, 'make:')) {
                    $this->devCacheDirty = true;
                }
                $this->observeConfigBoot($env);

                return $output;
            },
        );

        foreach ($inputs as $fragment => $answer) {
            \is_string($fragment) ? $interactive->answer($fragment, $answer) : throw new \InvalidArgumentException('runConsole() inputs must be keyed by prompt fragment.');
        }

        return $interactive->run();
    }

    // ------------------------------------------------------------- database

    /**
     * DB configuration lives in the profile; this is per-test isolation.
     * sqlite: delete the file(s) and recreate the schema. Real servers:
     * the drop/create dance — consecutive tests on the same worker must
     * never see prior schema or data. Cross-worker isolation comes from the
     * recipe's dbname_suffix (TEST_TOKEN) in both cases.
     */
    public function prepareDatabase(bool $createSchema = true): void
    {
        if (str_starts_with(ProfileCatalog::testDatabaseDsn(), 'sqlite')) {
            foreach ($this->sqliteDatabaseFiles() as $file) {
                $this->fs->remove($file);
            }
        } else {
            // makers run in the DEV env and diff against the dev database, so
            // BOTH envs get a pristine database (dbname_suffix separates them)
            foreach (['dev', 'test'] as $env) {
                $this->runConsole('doctrine:database:create --if-not-exists', env: $env);
                $this->runConsole('doctrine:database:drop --force', env: $env);
                $this->runConsole('doctrine:database:create', env: $env);
            }
        }

        if ($createSchema) {
            $this->runConsole('doctrine:schema:create', env: 'test');
        }
    }

    public function updateSchema(string $env = 'test'): void
    {
        $this->runConsole('doctrine:schema:update --force', env: $env);
    }

    /**
     * The migration executor: really runs every migration (up) against the
     * isolated test database, then confirms through Doctrine's own metadata
     * that the schema is up to date. Observes the generated migration files.
     */
    public function runMigrations(): void
    {
        $migrations = $this->proof->requiredOfType(ExecutionProof::TYPE_MIGRATION);
        $this->proof->declare($migrations, 'doctrine:migrations:migrate');

        $this->runConsole('doctrine:migrations:migrate --no-interaction --allow-no-migration', env: 'test');
        $this->runConsole('doctrine:migrations:up-to-date', env: 'test');

        $this->proof->observe($migrations);
    }

    /**
     * @return list<string> the DSN-derived sqlite path plus its
     *                      dbname_suffix variants
     */
    private function sqliteDatabaseFiles(): array
    {
        $path = str_replace(
            ['sqlite:///', '%kernel.project_dir%'],
            ['', $this->appDir],
            ProfileCatalog::testDatabaseDsn(),
        );
        $path = explode('?', $path, 2)[0];

        // never derive a deletion target outside the generated app: a DSN
        // without %kernel.project_dir% would resolve relative to the OUTER
        // phpunit process and delete files in the checkout
        if (!str_starts_with($path, rtrim($this->appDir, '/').'/')) {
            throw new \LogicException(\sprintf('Refusing to delete sqlite file "%s": TEST_DATABASE_DSN must point inside the app, e.g. sqlite:///%%kernel.project_dir%%/var/app.db.', $path));
        }

        return array_values(array_unique([$path, ...glob($path.'_test*') ?: []]));
    }

    // ------------------------------------------------------------ execution

    /**
     * Copies a PHPUnit test fixture into the app and runs exactly that file
     * with the app's own PHPUnit. The fixture resolves from
     * tests/fixtures/<maker-dir>/tests/<file>.
     *
     * @param list<string>          $covers  generated artifacts this run is expected
     *                                       to exercise; cross-checked against the
     *                                       files the run actually included
     * @param array<string, string> $replace strtr() replacements applied to the fixture
     */
    public function runGeneratedTests(string $fixtureTestFile, array $covers = [], array $replace = []): void
    {
        $source = $this->fixtures->path('tests/'.$fixtureTestFile);
        $contents = strtr(file_get_contents($source), $replace);

        if (!preg_match('/class\s+(\w+)/', $contents, $m)) {
            throw new \RuntimeException(\sprintf('Could not find a class name in fixture "%s".', $fixtureTestFile));
        }

        $testPath = 'tests/'.$m[1].'.php';
        file_put_contents($this->getPath($testPath), $contents);

        $this->runAppTests($testPath, $covers, \sprintf('runGeneratedTests(%s)', $fixtureTestFile));
    }

    /**
     * Real-execution primitive for cases with no dedicated fixture test:
     * renders a KernelTestCase (or WebTestCase) around $code and runs it
     * inside the app.
     *
     * @param list<string> $covers
     */
    public function assertGeneratedCodeRuns(string $code, array $covers = [], bool $webTestCase = false): void
    {
        $template = file_get_contents(Paths::harnessResourcesPath().'/RuntimeAssertionTest.php.tpl');

        $body = implode("\n", array_map(static fn ($line) => '        '.$line, explode("\n", trim($code))));
        $contents = strtr($template, [
            '%extends%' => $webTestCase ? 'WebTestCase' : 'KernelTestCase',
            '%setup%' => $webTestCase ? '' : '        self::bootKernel();',
            '%code%' => $body,
        ]);

        file_put_contents($this->getPath('tests/HarnessRuntimeTest.php'), $contents);

        $this->runAppTests('tests/HarnessRuntimeTest.php', $covers, 'assertGeneratedCodeRuns()');
    }

    /**
     * Runs a test file that already exists inside the app — e.g. one the maker
     * itself generated (the execution contract demands running the exact file).
     *
     * @param list<string> $covers
     */
    public function runGeneratedTestFile(string $relativeTestPath, array $covers = []): void
    {
        if (!$this->fileExists($relativeTestPath)) {
            throw new \InvalidArgumentException(\sprintf('No test file at "%s" in the app.', $relativeTestPath));
        }

        $this->runAppTests($relativeTestPath, $covers, \sprintf('runGeneratedTestFile(%s)', $relativeTestPath));
    }

    /**
     * @param list<string> $covers
     */
    private function runAppTests(string $relativeTestPath, array $covers, string $executor): void
    {
        $this->proof->declare($covers, $executor);

        $includeLog = $this->getPath('var/harness-includes.json');
        $twigLog = $this->getPath('var/harness-twig.json');
        $this->fs->remove([$includeLog, $twigLog]);
        $this->fs->mkdir($this->getPath('var'));

        $process = ProcessRunner::run(
            [
                'php',
                '-d', 'auto_prepend_file='.Paths::harnessResourcesPath().'/include-recorder.php',
                // the package's own launcher: independent of bin-dir proxies
                // and of the phpunit-bridge wrapper
                'vendor/phpunit/phpunit/phpunit',
                $relativeTestPath,
                '--do-not-cache-result',
            ],
            $this->appDir,
            env: [
                'MAKER_HARNESS_INCLUDE_LOG' => $includeLog,
                'MAKER_HARNESS_TWIG_LOG' => $twigLog,
            ],
            timeout: 120,
            allowFailure: true,
        );

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(\sprintf('Tests run inside the generated app failed (%s).\nApp path: "%s"\n\n%s', $relativeTestPath, $this->appDir, ProcessRunner::tail($process->getOutput()."\n".$process->getErrorOutput(), 80)));
        }

        $this->proof->observe($this->relativeIncludedFiles($includeLog));
        $this->proof->observe($this->tracedTemplates($twigLog));
        $this->observeConfigBoot('test');
    }

    /**
     * @return list<string> app-relative template paths the TwigTracerLoader saw
     */
    private function tracedTemplates(string $twigLog): array
    {
        if (!is_file($twigLog)) {
            return [];
        }

        $seen = json_decode(file_get_contents($twigLog), true);
        if (!\is_array($seen)) {
            return [];
        }

        return array_map(static fn (string $name) => 'templates/'.ltrim($name, '/'), array_keys($seen));
    }

    /**
     * Really executes a generated Stimulus controller: bundles the exact
     * generated source with the pinned toolchain in tests/Harness/js/,
     * registers it in a real Stimulus Application under jsdom, waits for it
     * to connect, and asserts its static API.
     *
     * @param array{targets?: list<string>, values?: list<string>, classes?: list<string>} $expect
     */
    public function assertStimulusControllerRuns(string $relativePath, array $expect = []): void
    {
        if (getenv('MAKER_SKIP_JS_EXEC')) {
            // skip the WHOLE case: execution proof must never be faked
            \PHPUnit\Framework\Assert::markTestSkipped('MAKER_SKIP_JS_EXEC is set.');
        }

        $toolchainDir = \dirname(__DIR__).'/js';
        self::ensureJsToolchain($toolchainDir);

        $identifier = str_replace('_', '-', preg_replace('/_controller\.(js|ts)$/', '', basename($relativePath)));

        $this->proof->declare([$relativePath], 'stimulus-runtime (esbuild + jsdom)');

        $process = ProcessRunner::run(
            ['node', $toolchainDir.'/run-stimulus.mjs', $this->getPath($relativePath), $identifier],
            $toolchainDir,
            timeout: 120,
            allowFailure: true,
        );

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(\sprintf("Generated Stimulus controller failed to execute (%s):\n%s", $relativePath, ProcessRunner::tail($process->getOutput()."\n".$process->getErrorOutput())));
        }

        $report = json_decode(trim($process->getOutput()), true);
        \PHPUnit\Framework\Assert::assertIsArray($report, 'The Stimulus runner did not report a result.');
        \PHPUnit\Framework\Assert::assertTrue($report['connected'] ?? false);

        foreach (['targets', 'values', 'classes'] as $api) {
            if (\array_key_exists($api, $expect)) {
                \PHPUnit\Framework\Assert::assertSame($expect[$api], $report[$api], \sprintf('Controller static %s mismatch.', $api));
            }
        }

        // coverage derives from the exact esbuild entry point
        $this->proof->observe([$relativePath]);
    }

    private static function ensureJsToolchain(string $toolchainDir): void
    {
        if (is_dir($toolchainDir.'/node_modules/@hotwired/stimulus')) {
            return;
        }

        $handle = fopen($toolchainDir.'/.install.lock', 'c');
        if (!$handle || !flock($handle, \LOCK_EX)) {
            throw new \RuntimeException('Could not lock the JS toolchain install.');
        }

        try {
            if (!is_dir($toolchainDir.'/node_modules/@hotwired/stimulus')) {
                ProcessRunner::run(['npm', 'ci', '--no-audit', '--no-fund'], $toolchainDir, timeout: 300);
            }
        } finally {
            flock($handle, \LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * The DOCUMENTED EXCEPTION of the execution contract: compose files are
     * validated with `docker compose config`, not executed against real
     * containers. If docker compose is unavailable, the whole case is skipped:
     * validation proof is never marked satisfied without running it.
     */
    public function assertComposeFileIsValid(string $relativePath = 'compose.yaml'): void
    {
        $probe = ProcessRunner::run(['docker', 'compose', 'version'], $this->appDir, timeout: 30, allowFailure: true);
        if (!$probe->isSuccessful()) {
            \PHPUnit\Framework\Assert::markTestSkipped('docker compose is not available.');
        }

        $process = ProcessRunner::run(
            ['docker', 'compose', '-f', $this->getPath($relativePath), 'config'],
            $this->appDir,
            timeout: 60,
            allowFailure: true,
        );

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(\sprintf("Generated compose file \"%s\" failed `docker compose config` validation:\n%s", $relativePath, ProcessRunner::tail($process->getOutput()."\n".$process->getErrorOutput())));
        }

        $this->proof->recordValidated($relativePath);
    }

    /**
     * The only door for composer-level mutations: regenerates the autoloader
     * and marks vendor dirty so the next reset restores vendor/ in full and
     * reapplies the bundle projection.
     */
    public function dumpAutoload(): void
    {
        ProcessRunner::run(['composer', 'dump-autoload', '--no-interaction'], $this->appDir, timeout: 300);
        AppPool::markVendorDirty($this->profileName);
    }

    /**
     * Registers an extra PSR-4 namespace in the app's autoloader (used to
     * simulate vendor-provided classes). Composer-level mutation: implies the
     * full vendor restore of dumpAutoload().
     */
    public function addToAutoloader(string $namespace, string $relativePath): void
    {
        $composerJson = json_decode($this->readFile('composer.json'), true);
        $composerJson['autoload']['psr-4'][$namespace] = $relativePath;
        file_put_contents($this->getPath('composer.json'), json_encode($composerJson, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));

        $this->devCacheDirty = true;
        $this->dumpAutoload();
    }

    // ----------------------------------------------------------- assertions

    public function assertFileExists(string $relativePath): void
    {
        \PHPUnit\Framework\Assert::assertFileExists($this->getPath($relativePath));
    }

    public function assertFileContains(string $relativePath, string $needle): void
    {
        FileAssertions::assertFileContains($this->getPath($relativePath), $needle);
    }

    public function assertFileNotContains(string $relativePath, string $needle): void
    {
        FileAssertions::assertFileNotContains($this->getPath($relativePath), $needle);
    }

    /**
     * Byte-comparison (EOL-normalized) against a fixture, e.g.
     * assertFileMatchesFixture('src/Controller/FooController.php', 'expected/FooController.php').
     */
    public function assertFileMatchesFixture(string $relativePath, string $fixturePath): void
    {
        FileAssertions::assertFileEqualsFile($this->fixtures->path($fixturePath), $this->getPath($relativePath));
    }

    // ------------------------------------------------------------- lifecycle

    /**
     * Called from tearDown on success: batched twig style lint + the
     * execution-contract set-difference check.
     */
    public function finalize(): void
    {
        TwigCsLinter::lint($this->appDir, $this->proof->requiredOfType(ExecutionProof::TYPE_TWIG));
        $this->proof->check();
    }

    private function observeConfigBoot(string $env): void
    {
        $loaded = $this->proof->configPathsLoadedBy($env);
        $this->proof->declare($loaded, \sprintf('kernel boot (env=%s)', $env));
        $this->proof->observe($loaded);
    }

    private function clearDevCacheIfDirty(): void
    {
        if ($this->devCacheDirty) {
            $this->fs->remove($this->getPath('var/cache/dev'));
            $this->devCacheDirty = false;
        }
    }

    private function guardMutation(string $relativePath): void
    {
        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');

        if ('composer.json' === $normalized || 'composer.lock' === $normalized) {
            throw new \LogicException('Raw composer.json/composer.lock writes are prohibited: composer-level changes need dump-autoload and a full vendor reset. Use dumpAutoload() after a composer-aware mutation, or preinstall the package in the profile.');
        }

        if (str_starts_with($normalized, 'config/') || str_starts_with($normalized, 'src/') || str_starts_with(basename($normalized), '.env')) {
            $this->devCacheDirty = true;
        }

        if (str_starts_with($normalized, 'vendor/')) {
            AppPool::markVendorDirty($this->profileName);
        }
    }

    /**
     * @return list<string> app-relative paths of everything the nested PHPUnit
     *                      process actually included
     */
    private function relativeIncludedFiles(string $includeLog): array
    {
        if (!is_file($includeLog)) {
            return [];
        }

        $files = json_decode(file_get_contents($includeLog), true);
        if (!\is_array($files)) {
            return [];
        }

        // on Windows, get_included_files() reports native backslash paths
        // while $appDir uses forward slashes; without normalizing both sides
        // the prefix never matches and no execution is ever observed
        $prefix = rtrim(str_replace('\\', '/', $this->appDir), '/').'/';
        $relative = [];
        foreach ($files as $file) {
            if (!\is_string($file)) {
                continue;
            }
            $file = str_replace('\\', '/', $file);
            if (str_starts_with($file, $prefix)) {
                $relative[] = substr($file, \strlen($prefix));
            }
        }

        return $relative;
    }
}
