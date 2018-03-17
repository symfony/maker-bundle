<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle;

final class DependencyBuilder
{
    private $dependencies = [];
    private $devDependencies = [];

    /**
     * Add a dependency that will be reported if the given class is missing.
     *
     * If the dependency is *optional*, then it will only be reported to
     * the user if other required dependencies are missing. An example
     * is the "validator" when trying to work with forms.
     */
    public function addClassDependency(string $class, string $package, bool $required = true, bool $devDependency = false)
    {
        if ($devDependency) {
            $this->devDependencies[] = [
                'class' => $class,
                'name' => $package,
                'required' => $required,
            ];
        } else {
            $this->dependencies[] = [
                'class' => $class,
                'name' => $package,
                'required' => $required,
            ];
        }
    }

    /**
     * @internal
     */
    public function getMissingDependencies(): array
    {
        return $this->calculateMissingDependencies($this->dependencies);
    }

    /**
     * @internal
     */
    public function getMissingDevDependencies(): array
    {
        return $this->calculateMissingDependencies($this->devDependencies);
    }

    /**
     * @internal
     */
    public function getAllRequiredDependencies(): array
    {
        return $this->getRequiredDependencyNames($this->dependencies);
    }

    /**
     * @internal
     */
    public function getAllRequiredDevDependencies(): array
    {
        return $this->getRequiredDependencyNames($this->devDependencies);
    }

    /**
     * @internal
     */
    public function getMissingPackagesMessage(string $commandName): string
    {
        $packages = $this->getMissingDependencies();
        $packagesDev = $this->getMissingDevDependencies();

        if (empty($packages) && empty($packagesDev)) {
            return '';
        }

        $packagesCount = count($packages) + count($packagesDev);

        $message = sprintf(
            "Missing package%s: to use the %s command, run:\n",
            $packagesCount > 1 ? 's' : '',
            $commandName
        );

        if (!empty($packages)) {
            $message .= sprintf("\ncomposer require %s", implode(' ', $packages));
        }

        if (!empty($packagesDev)) {
            $message .= sprintf("\ncomposer require %s --dev", implode(' ', $packagesDev));
        }

        return $message;
    }

    private function getRequiredDependencyNames(array $dependencies)
    {
        $packages = [];
        foreach ($dependencies as $package) {
            if (!$package['required']) {
                continue;
            }
            $packages[] = $package['name'];
        }

        return array_unique($packages);
    }

    private function calculateMissingDependencies(array $dependencies)
    {
        $missingPackages = [];
        $missingOptionalPackages = [];
        foreach ($dependencies as $package) {
            if (class_exists($package['class'])) {
                continue;
            }
            if (true === $package['required']) {
                $missingPackages[] = $package['name'];
            } else {
                $missingOptionalPackages[] = $package['name'];
            }
        }
        if (empty($missingPackages)) {
            return [];
        }

        return array_unique(array_merge($missingPackages, $missingOptionalPackages));
    }
}
