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

use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\MakerInterface;

final class MakerTestDetails
{
    private ?\Closure $runCallback = null;
    private array $preRunCallbacks = [];
    private array $extraDependencies = [];
    private string $rootNamespace = 'App';
    private int $requiredPhpVersion = 80000;
    private array $requiredPackageVersions = [];
    private int $blockedPhpVersionUpper = 0;
    private int $blockedPhpVersionLower = 0;

    public function __construct(
        private MakerInterface $maker,
    ) {
    }

    public function run(\Closure $callback): self
    {
        $this->runCallback = $callback;

        return $this;
    }

    public function preRun(\Closure $callback): self
    {
        $this->preRunCallbacks[] = $callback;

        return $this;
    }

    /**
     * @return string
     */
    public function getRootNamespace()
    {
        return $this->rootNamespace;
    }

    public function changeRootNamespace(string $rootNamespace): self
    {
        $this->rootNamespace = trim($rootNamespace, '\\');

        return $this;
    }

    public function addExtraDependencies(string ...$packages): self
    {
        $this->extraDependencies += $packages;

        return $this;
    }

    public function setRequiredPhpVersion(int $version): self
    {
        @trigger_deprecation('symfony/maker-bundle', 'v1.44.0', 'setRequiredPhpVersion() is no longer used and will be removed in a future version.');

        $this->requiredPhpVersion = $version;

        return $this;
    }

    /**
     * Skip a test from running between a range of PHP Versions.
     *
     * @param int $lowerLimit Versions below this value will be allowed
     * @param int $upperLimit Versions above this value will be allowed
     *
     * @internal
     */
    public function setSkippedPhpVersions(int $lowerLimit, int $upperLimit): self
    {
        $this->blockedPhpVersionUpper = $upperLimit;
        $this->blockedPhpVersionLower = $lowerLimit;

        return $this;
    }

    public function addRequiredPackageVersion(string $packageName, string $versionConstraint): self
    {
        $this->requiredPackageVersions[] = ['name' => $packageName, 'version_constraint' => $versionConstraint];

        return $this;
    }

    public function getUniqueCacheDirectoryName(): string
    {
        // for cache purposes, only the dependencies are important!
        // You can change it ONLY if you don't have another way to implement it
        return 'maker_'.strtolower($this->getRootNamespace()).'_'.md5(serialize($this->getDependencies()));
    }

    public function getMaker(): MakerInterface
    {
        return $this->maker;
    }

    public function getDependencies(): array
    {
        $depBuilder = $this->getDependencyBuilder();

        return array_merge(
            $depBuilder->getAllRequiredDependencies(),
            $depBuilder->getAllRequiredDevDependencies(),
            $this->extraDependencies
        );
    }

    public function getExtraDependencies(): array
    {
        return $this->extraDependencies;
    }

    public function getDependencyBuilder(): DependencyBuilder
    {
        $depBuilder = new DependencyBuilder();
        $this->maker->configureDependencies($depBuilder);

        return $depBuilder;
    }

    public function isSupportedByCurrentPhpVersion(): bool
    {
        $hasPhpVersionConstraint = $this->blockedPhpVersionLower > 0 && $this->blockedPhpVersionUpper > 0;
        $isSupported = false;

        if (!$hasPhpVersionConstraint) {
            $isSupported = true;
        }

        if (\PHP_VERSION_ID > $this->blockedPhpVersionUpper) {
            $isSupported = true;
        }

        if (\PHP_VERSION_ID < $this->blockedPhpVersionLower) {
            $isSupported = true;
        }

        return $isSupported && \PHP_VERSION_ID >= $this->requiredPhpVersion;
    }

    public function getRequiredPackageVersions(): array
    {
        return $this->requiredPackageVersions;
    }

    public function getRunCallback(): \Closure
    {
        if (!$this->runCallback) {
            throw new \Exception('Don\'t forget to call ->run()');
        }

        return $this->runCallback;
    }

    /**
     * @return \Closure[]
     */
    public function getPreRunCallbacks(): array
    {
        return $this->preRunCallbacks;
    }
}
