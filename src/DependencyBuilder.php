<?php

namespace Symfony\Bundle\MakerBundle;

final class DependencyBuilder
{
    private $dependencies = [];

    /**
     * Add a dependency that will be reported if the given class is missing.
     *
     * If the dependency is *optional*, then it will only be reported to
     * the user if other required dependencies are missing. An example
     * is the "validator" when trying to work with forms.
     *
     * @param string $class
     * @param string $package
     * @param bool $required
     */
    public function addClassDependency($class, $package, $required = true)
    {
        $this->dependencies[$class] = [
            'name' => $package,
            'required' => $required
        ];
    }

    public function getMissingDependencies()
    {
        $missingPackages = [];
        $missingOptionalPackages = [];

        foreach ($this->dependencies as $class => $package) {
            if (class_exists($class)) {
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

        return array_merge($missingPackages, $missingOptionalPackages);
    }
}