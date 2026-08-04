<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness;

use Composer\Semver\Semver;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\AppPool;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\GeneratedApp;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Paths;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\ProfileCatalog;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Resetter\ResetterFactory;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\DirtiesVendor;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\RequiresPackage;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\RequiresSymfony;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\SkipIfEnv;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\SkipOnPostgres;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\SkipOnWindows;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\UsesProfile;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\WindowsSmoke;

/**
 * Base class of the functional suite. Configuration is declarative:
 *
 *   #[MakerTest(maker: MakeCommand::class, profile: Profiles::BASE)]
 *   final class MakeCommandTest extends MakerTestCase
 *   {
 *       public function testItMakesACommand()
 *       {
 *           $this->app->runMaker()->answer('Choose a command name', 'app:foo')->run();
 *           ...
 *       }
 *   }
 */
abstract class MakerTestCase extends TestCase
{
    protected GeneratedApp $app;
    private string $profileName;

    protected function setUp(): void
    {
        /** @var \ReflectionClass<object> $class */
        $class = new \ReflectionClass(static::class);
        $method = $class->getMethod($this->testMethodName());

        /** @var MakerTest|null $makerTest */
        $makerTest = ($class->getAttributes(MakerTest::class)[0] ?? null)?->newInstance();
        if (null === $makerTest) {
            throw new \LogicException(\sprintf('"%s" must declare a #[MakerTest(maker: ..., profile: ...)] attribute.', static::class));
        }

        foreach ([...$class->getAttributes(SkipIfEnv::class), ...$method->getAttributes(SkipIfEnv::class)] as $attribute) {
            $variable = $attribute->newInstance()->variable;
            if (filter_var(getenv($variable), \FILTER_VALIDATE_BOOL)) {
                $this->markTestSkipped(\sprintf('Skipped because %s is set.', $variable));
            }
        }

        if ('\\' === \DIRECTORY_SEPARATOR) {
            foreach ([...$class->getAttributes(SkipOnWindows::class), ...$method->getAttributes(SkipOnWindows::class)] as $attribute) {
                $this->markTestSkipped($attribute->newInstance()->reason ?: 'Skipped on Windows.');
            }
        }

        if (filter_var(getenv('MAKER_WINDOWS_SMOKE'), \FILTER_VALIDATE_BOOL)
            && !$class->getAttributes(WindowsSmoke::class) && !$method->getAttributes(WindowsSmoke::class)
        ) {
            $this->markTestSkipped('Not part of the Windows smoke subset (MAKER_WINDOWS_SMOKE).');
        }

        if (str_starts_with(ProfileCatalog::testDatabaseDsn(), 'postgres')) {
            foreach ([...$class->getAttributes(SkipOnPostgres::class), ...$method->getAttributes(SkipOnPostgres::class)] as $attribute) {
                $this->markTestSkipped($attribute->newInstance()->reason ?: 'Skipped on PostgreSQL.');
            }
        }

        /** @var UsesProfile|null $usesProfile */
        $usesProfile = ($method->getAttributes(UsesProfile::class)[0] ?? null)?->newInstance();
        $this->profileName = $usesProfile instanceof UsesProfile ? $usesProfile->profile : $makerTest->profile;

        $appDir = AppPool::preparedAppDir($this->profileName);

        $this->enforceVersionRequirements($class, $method, $appDir);

        $makerClass = $makerTest->maker;
        $this->app = new GeneratedApp($appDir, $this->profileName, $makerClass::getCommandName());

        if ($class->getAttributes(DirtiesVendor::class) || $method->getAttributes(DirtiesVendor::class)) {
            AppPool::markVendorDirty($this->profileName);
        }
    }

    protected function tearDown(): void
    {
        if (!isset($this->app)) {
            return;
        }

        if (!$this->testHasPassed()) {
            if (getenv('MAKER_KEEP_APP')) {
                $keptDir = Paths::keptDir(static::class.'::'.$this->testMethodName());
                ResetterFactory::best()->syncTree($this->app->getPath(), $keptDir);
                fwrite(\STDERR, \sprintf("\n[MAKER_KEEP_APP] failed app preserved at %s\n", $keptDir));
            }

            return;
        }

        // Only enforced on otherwise-passing tests, so contract violations
        // never mask the real failure.
        $this->app->finalize();

        // the execution-contract check IS a verification; without this, tests
        // whose assertions all run in subprocesses would be marked risky
        $this->addToAssertionCount(1);
    }

    /**
     * @param \ReflectionClass<object> $class
     */
    private function enforceVersionRequirements(\ReflectionClass $class, \ReflectionMethod $method, string $appDir): void
    {
        $installed = self::installedAppPackages($appDir);

        foreach ([...$class->getAttributes(RequiresPackage::class), ...$method->getAttributes(RequiresPackage::class)] as $attribute) {
            /** @var RequiresPackage $requirement */
            $requirement = $attribute->newInstance();
            $version = $installed[$requirement->package] ?? null;

            if (null === $version) {
                $this->markTestSkipped(\sprintf('Package "%s" is not installed in the "%s" profile app.', $requirement->package, $this->profileName));
            }
            if ('*' !== $requirement->versionConstraint && !Semver::satisfies($version, $requirement->versionConstraint)) {
                $this->markTestSkipped(\sprintf('Package "%s" %s does not satisfy "%s".', $requirement->package, $version, $requirement->versionConstraint));
            }
        }

        foreach ([...$class->getAttributes(RequiresSymfony::class), ...$method->getAttributes(RequiresSymfony::class)] as $attribute) {
            /** @var RequiresSymfony $requirement */
            $requirement = $attribute->newInstance();
            $version = $installed['symfony/framework-bundle'] ?? null;

            if (null === $version || !Semver::satisfies($version, $requirement->versionConstraint)) {
                $this->markTestSkipped(\sprintf('App Symfony version %s does not satisfy "%s".', $version ?? '(unknown)', $requirement->versionConstraint));
            }
        }
    }

    /**
     * @return array<string, string> package => pretty version
     */
    private static function installedAppPackages(string $appDir): array
    {
        $file = $appDir.'/vendor/composer/installed.php';
        if (!is_file($file)) {
            return [];
        }

        $data = require $file;
        $packages = [];
        foreach ($data['versions'] ?? [] as $package => $info) {
            if (isset($info['pretty_version'])) {
                $packages[$package] = ltrim($info['pretty_version'], 'v');
            }
        }

        return $packages;
    }

    private function testMethodName(): string
    {
        // PHPUnit 10+ has name(); PHPUnit 9 (the current simple-phpunit) has
        // getName(). One branch is always "impossible" for phpstan depending
        // on which PHPUnit its includes resolve; ignored in phpstan.dist.neon
        // with reportUnmatched: false.
        if (method_exists($this, 'name')) {
            return $this->name();
        }

        return $this->getName(false);
    }

    private function testHasPassed(): bool
    {
        if (method_exists($this, 'status')) {
            return $this->status()->isSuccess();
        }

        return \PHPUnit\Runner\BaseTestRunner::STATUS_PASSED === $this->getStatus();
    }
}
