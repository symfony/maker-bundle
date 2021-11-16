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
    private $maker;

    private $runCallback;

    private $preRunCallbacks = [];

    private $extraDependencies = [];

    private $rootNamespace = 'App';

    private $requiredPhpVersion;

    private $requiredPackageVersions = [];

    public function __construct(MakerInterface $maker)
    {
        $this->maker = $maker;
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
        $this->requiredPhpVersion = $version;

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
        return null === $this->requiredPhpVersion || \PHP_VERSION_ID >= $this->requiredPhpVersion;
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
