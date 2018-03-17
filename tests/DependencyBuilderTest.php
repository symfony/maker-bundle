<?php

namespace Symfony\Bundle\MakerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\DependencyBuilder;

class DependencyBuilderTest extends TestCase
{
    public function testGetAllRequiredDependencies()
    {
        $depBuilder = new DependencyBuilder();
        $depBuilder->addClassDependency('Foo', 'foo-package');
        $depBuilder->addClassDependency('Bar', 'bar-package');
        $depBuilder->addClassDependency('DevStuff', 'dev-stuff-package', true, true);
        $depBuilder->addClassDependency('DevStuff2', 'dev-stuff2-package', true, true);
        $depBuilder->addClassDependency('DevStuff3', 'dev-stuff2-package', true, true);

        $this->assertSame(['foo-package', 'bar-package'], $depBuilder->getAllRequiredDependencies());
        $this->assertSame(['dev-stuff-package', 'dev-stuff2-package'], $depBuilder->getAllRequiredDevDependencies());
    }

    public function testGetMissingDependencies()
    {
        $depBuilder = new DependencyBuilder();
        $depBuilder->addClassDependency('Foo', 'foo-package');
        $depBuilder->addClassDependency('Bar', 'bar-package', false);
        $depBuilder->addClassDependency('DevStuff', 'dev-stuff-package', true, true);
        $depBuilder->addClassDependency('DevStuff2', 'dev-stuff2-package', false, true);

        $this->assertSame(['foo-package', 'bar-package'], $depBuilder->getMissingDependencies());
        $this->assertSame(['dev-stuff-package', 'dev-stuff2-package'], $depBuilder->getMissingDevDependencies());
    }

    public function testGetMissingDependenciesNoMissingRequired()
    {
        $depBuilder = new DependencyBuilder();
        $depBuilder->addClassDependency(__CLASS__, 'foo-package');
        $depBuilder->addClassDependency('Bar', 'bar-package', false);

        $actualDeps = $depBuilder->getMissingDependencies();
        $this->assertSame([], $actualDeps);
    }

    /**
     * @dataProvider getMissingPackagesMessageTests
     */
    public function testGetMissingPackagesMessage(array $missingPackages, array $missingDevPackages, string $expectedMessage)
    {
        $depBuilder = new DependencyBuilder();
        foreach ($missingPackages as $missingPackage) {
            $depBuilder->addClassDependency('Foo', $missingPackage);
        }

        foreach ($missingDevPackages as $missingPackage) {
            $depBuilder->addClassDependency('Foo', $missingPackage, true, true);
        }

        // normalize line breaks on Windows for comparison
        $expectedMessage = str_replace("\r\n", "\n", $expectedMessage);
        $this->assertSame($expectedMessage, $depBuilder->getMissingPackagesMessage('make:something'));
    }

    public function getMissingPackagesMessageTests()
    {
        yield 'nothing_missing' => [
            [],
            [],
            ''
        ];

        yield 'missing_one_package' => [
            ['bar-package'],
            [],
            <<<EOF
Missing package: to use the make:something command, run:

composer require bar-package
EOF
        ];

        yield 'missing_multiple_packages' => [
            ['bar-package', 'other-package'],
            [],
            <<<EOF
Missing packages: to use the make:something command, run:

composer require bar-package other-package
EOF
        ];

        yield 'missing_dev_packages' => [
            [],
            ['bar-package', 'other-package'],
            <<<EOF
Missing packages: to use the make:something command, run:

composer require bar-package other-package --dev
EOF
        ];
    }
}
