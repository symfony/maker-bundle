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

use Composer\Semver\Semver;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

abstract class MakerTestCase extends TestCase
{
    private ?KernelInterface $kernel = null;

    /**
     * @dataProvider getTestDetails
     *
     * @return void
     */
    public function testExecute(MakerTestDetails $makerTestDetails)
    {
        $this->executeMakerCommand($makerTestDetails);
    }

    abstract public function getTestDetails();

    abstract protected function getMakerClass(): string;

    protected function createMakerTest(): MakerTestDetails
    {
        return new MakerTestDetails($this->getMakerInstance($this->getMakerClass()));
    }

    /**
     * @return void
     */
    protected function executeMakerCommand(MakerTestDetails $testDetails)
    {
        if (!class_exists(Process::class)) {
            throw new \LogicException('The MakerTestCase cannot be run as the Process component is not installed. Try running "compose require --dev symfony/process".');
        }

        if (!$testDetails->isSupportedByCurrentPhpVersion()) {
            $this->markTestSkipped();
        }

        if ($testDetails->skipOnWindows() && '\\' === \DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('This test is not supported on Windows');
        }

        $testEnv = MakerTestEnvironment::create($testDetails);

        // prepare environment to test
        $testEnv->prepareDirectory();

        if (!$this->hasRequiredDependencyVersions($testDetails, $testEnv)) {
            $this->markTestSkipped('Some dependencies versions are too low');
        }

        $makerRunner = new MakerTestRunner($testEnv);
        foreach ($testDetails->getPreRunCallbacks() as $preRunCallback) {
            $preRunCallback($makerRunner);
        }

        $callback = $testDetails->getRunCallback();
        $callback($makerRunner);

        // run tests
        $files = $testEnv->getGeneratedFilesFromOutputText();

        foreach ($files as $file) {
            $this->assertTrue($testEnv->fileExists($file), sprintf('The file "%s" does not exist after generation', $file));

            if (str_ends_with($file, '.twig')) {
                $csProcess = $testEnv->runTwigCSLint($file);

                $this->assertTrue($csProcess->isSuccessful(), sprintf('File "%s" has a twig-cs problem: %s', $file, $csProcess->getErrorOutput()."\n".$csProcess->getOutput()));
            }
        }
    }

    /**
     * @return void
     */
    protected function assertContainsCount(string $needle, string $haystack, int $count)
    {
        $this->assertEquals(1, substr_count($haystack, $needle), sprintf('Found more than %d occurrences of "%s" in "%s"', $count, $needle, $haystack));
    }

    private function getMakerInstance(string $makerClass): MakerInterface
    {
        if (null === $this->kernel) {
            $this->kernel = $this->createKernel();
            $this->kernel->boot();
        }

        // a cheap way to guess the service id
        $serviceId ??= sprintf('maker.maker.%s', Str::asSnakeCase((new \ReflectionClass($makerClass))->getShortName()));

        return $this->kernel->getContainer()->get($serviceId);
    }

    protected function createKernel(): KernelInterface
    {
        return new MakerTestKernel('dev', true);
    }

    private function hasRequiredDependencyVersions(MakerTestDetails $testDetails, MakerTestEnvironment $testEnv): bool
    {
        if (empty($testDetails->getRequiredPackageVersions())) {
            return true;
        }

        $installedPackages = json_decode($testEnv->readFile('vendor/composer/installed.json'), true, 512, \JSON_THROW_ON_ERROR);
        $packageVersions = [];

        foreach ($installedPackages['packages'] ?? $installedPackages as $installedPackage) {
            $packageVersions[$installedPackage['name']] = $installedPackage['version_normalized'];
        }

        foreach ($testDetails->getRequiredPackageVersions() as $requiredPackageData) {
            $name = $requiredPackageData['name'];
            $versionConstraint = $requiredPackageData['version_constraint'];

            if (!isset($packageVersions[$name])) {
                throw new \Exception(sprintf('Package "%s" is required in the test project at version "%s" but it is not installed?', $name, $versionConstraint));
            }

            if (!Semver::satisfies($packageVersions[$name], $versionConstraint)) {
                return false;
            }
        }

        return true;
    }
}
