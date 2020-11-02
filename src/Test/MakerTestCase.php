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

abstract class MakerTestCase extends TestCase
{
    private $kernel;

    /**
     * @dataProvider getTestDetails
     */
    public function testExecute(MakerTestDetails $makerTestDetails)
    {
        $this->executeMakerCommand($makerTestDetails);
    }

    abstract public function getTestDetails();

    protected function executeMakerCommand(MakerTestDetails $testDetails)
    {
        if (!$testDetails->isSupportedByCurrentPhpVersion()) {
            $this->markTestSkipped();
        }

        $testEnv = MakerTestEnvironment::create($testDetails);

        // prepare environment to test
        $testEnv->prepare();

        if (!$this->hasRequiredDependencyVersions($testDetails, $testEnv)) {
            $this->markTestSkipped('Some dependencies versions are too low');
        }

        // run tests
        $makerTestProcess = $testEnv->runMaker();
        $files = $testEnv->getGeneratedFilesFromOutputText();

        foreach ($files as $file) {
            $this->assertTrue($testEnv->fileExists($file), sprintf('The file "%s" does not exist after generation', $file));

            if (\PHP_VERSION_ID >= 80000) {
                continue;
            }

            if ('.php' === substr($file, -4)) {
                $csProcess = $testEnv->runPhpCSFixer($file);

                $this->assertTrue($csProcess->isSuccessful(), sprintf(
                    "File '%s' has a php-cs problem: %s\n",
                    $file,
                    $csProcess->getErrorOutput()."\n".$csProcess->getOutput()
                ));
            }

            if ('.twig' === substr($file, -5)) {
                $csProcess = $testEnv->runTwigCSLint($file);

                $this->assertTrue($csProcess->isSuccessful(), sprintf('File "%s" has a twig-cs problem: %s', $file, $csProcess->getErrorOutput()."\n".$csProcess->getOutput()));
            }
        }

        // run internal tests
        $internalTestProcess = $testEnv->runInternalTests();
        if (null !== $internalTestProcess) {
            $this->assertTrue($internalTestProcess->isSuccessful(), sprintf("Error while running the PHPUnit tests *in* the project: \n\n %s \n\n Command Output: %s", $internalTestProcess->getErrorOutput()."\n".$internalTestProcess->getOutput(), $makerTestProcess->getErrorOutput()."\n".$makerTestProcess->getOutput()));
        }

        // checkout user asserts
        if (null === $testDetails->getAssert()) {
            $this->assertStringContainsString('Success', $makerTestProcess->getOutput(), $makerTestProcess->getErrorOutput());
        } else {
            ($testDetails->getAssert())($makerTestProcess->getOutput(), $testEnv->getPath());
        }
    }

    protected function assertContainsCount(string $needle, string $haystack, int $count)
    {
        $this->assertEquals(1, substr_count($haystack, $needle), sprintf('Found more than %d occurrences of "%s" in "%s"', $count, $needle, $haystack));
    }

    protected function getMakerInstance(string $makerClass): MakerInterface
    {
        if (null === $this->kernel) {
            $this->kernel = new MakerTestKernel('dev', true);
            $this->kernel->boot();
        }

        // a cheap way to guess the service id
        $serviceId = $serviceId ?? sprintf('maker.maker.%s', Str::asRouteName((new \ReflectionClass($makerClass))->getShortName()));

        return $this->kernel->getContainer()->get($serviceId);
    }

    private function hasRequiredDependencyVersions(MakerTestDetails $testDetails, MakerTestEnvironment $testEnv): bool
    {
        if (empty($testDetails->getRequiredPackageVersions())) {
            return true;
        }

        $installedPackages = json_decode($testEnv->readFile('vendor/composer/installed.json'), true);
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
