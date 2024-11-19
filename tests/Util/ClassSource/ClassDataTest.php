<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Util\ClassSource;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MakerBundle\Test\MakerTestKernel;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassData;

class ClassDataTest extends TestCase
{
    public function testStaticConstructor(): void
    {
        $meta = ClassData::create(MakerBundle::class);

        // Sanity check in case Maker's NS changes
        self::assertSame('Symfony\Bundle\MakerBundle\MakerBundle', MakerBundle::class);

        self::assertSame('MakerBundle', $meta->getClassName());
        self::assertSame('App\Symfony\Bundle\MakerBundle', $meta->getNamespace());
        self::assertSame('App\Symfony\Bundle\MakerBundle\MakerBundle', $meta->getFullClassName());
    }

    public function testGetClassDeclaration(): void
    {
        $meta = ClassData::create(MakerBundle::class);

        self::assertSame('final class MakerBundle', $meta->getClassDeclaration());
    }

    public function testIsFinal(): void
    {
        $meta = ClassData::create(MakerBundle::class);

        // Default - isFinal - true
        self::assertSame('final class MakerBundle', $meta->getClassDeclaration());

        // Not Final - isFinal - false
        $meta->setIsFinal(false);
        self::assertSame('class MakerBundle', $meta->getClassDeclaration());
    }

    public function testGetClassDeclarationWithExtends(): void
    {
        $meta = ClassData::create(class: MakerBundle::class, extendsClass: MakerTestKernel::class);

        self::assertSame('final class MakerBundle extends MakerTestKernel', $meta->getClassDeclaration());
    }

    /** @dataProvider suffixDataProvider */
    public function testSuffix(?string $suffix, string $expectedResult): void
    {
        $data = ClassData::create(class: MakerBundle::class, suffix: $suffix);

        self::assertSame($expectedResult, $data->getClassName());
    }

    public function suffixDataProvider(): \Generator
    {
        yield [null, 'MakerBundle'];
        yield ['Testing', 'MakerBundleTesting'];
        yield ['Bundle', 'MakerBundle'];
    }

    /** @dataProvider namespaceDataProvider */
    public function testNamespace(string $class, ?string $rootNamespace, string $expectedNamespace, string $expectedFullClassName): void
    {
        $class = ClassData::create($class);

        if (null !== $rootNamespace) {
            $class->setRootNamespace($rootNamespace);
        }

        self::assertSame($expectedNamespace, $class->getNamespace());
        self::assertSame($expectedFullClassName, $class->getFullClassName());
    }

    public function namespaceDataProvider(): \Generator
    {
        yield ['MyController', null, 'App', 'App\MyController'];
        yield ['Controller\MyController', null, 'App\Controller', 'App\Controller\MyController'];
        yield ['MyController', 'Maker', 'Maker', 'Maker\MyController'];
        yield ['Controller\MyController', 'Maker', 'Maker\Controller', 'Maker\Controller\MyController'];
    }

    public function testGetClassName(): void
    {
        $class = ClassData::create(class: 'Controller\\Foo', suffix: 'Controller');
        self::assertSame('FooController', $class->getClassName());
        self::assertSame('Foo', $class->getClassName(relative: false, withoutSuffix: true));
        self::assertSame('FooController', $class->getClassName(relative: true, withoutSuffix: false));
        self::assertSame('Foo', $class->getClassName(relative: true, withoutSuffix: true));
        self::assertSame('App\Controller\FooController', $class->getFullClassName());
    }

    public function testGetClassNameRelativeNamespace(): void
    {
        $class = ClassData::create(class: 'Controller\\Admin\\Foo', suffix: 'Controller');
        self::assertSame('FooController', $class->getClassName());
        self::assertSame('Foo', $class->getClassName(relative: false, withoutSuffix: true));
        self::assertSame('Admin\FooController', $class->getClassName(relative: true, withoutSuffix: false));
        self::assertSame('Admin\Foo', $class->getClassName(relative: true, withoutSuffix: true));
        self::assertSame('App\Controller\Admin\FooController', $class->getFullClassName());
    }

    public function testGetClassNameWithAbsoluteNamespace(): void
    {
        $class = ClassData::create(class: '\\Foo\\Bar\\Admin\\Baz', suffix: 'Controller');
        self::assertSame('BazController', $class->getClassName());
        self::assertSame('Foo\Bar\Admin', $class->getNamespace());
        self::assertSame('Foo\Bar\Admin\BazController', $class->getFullClassName());
    }

    /** @dataProvider fullClassNameProvider */
    public function testGetFullClassName(string $class, ?string $rootNamespace, bool $withoutRootNamespace, bool $withoutSuffix, string $expectedFullClassName): void
    {
        $class = ClassData::create($class, suffix: 'Controller');

        if (null !== $rootNamespace) {
            $class->setRootNamespace($rootNamespace);
        }

        self::assertSame($expectedFullClassName, $class->getFullClassName(withoutRootNamespace: $withoutRootNamespace, withoutSuffix: $withoutSuffix));
    }

    public function fullClassNameProvider(): \Generator
    {
        yield ['Controller\MyController', null, false, false, 'App\Controller\MyController'];
        yield ['Controller\MyController', null, true, false, 'Controller\MyController'];
        yield ['Controller\MyController', null, false, true, 'App\Controller\My'];
        yield ['Controller\MyController', null, true, true, 'Controller\My'];
        yield ['Controller\MyController', 'Custom', false, false, 'Custom\Controller\MyController'];
        yield ['Controller\MyController', 'Custom', true, false, 'Controller\MyController'];
        yield ['Controller\MyController', 'Custom', false, true, 'Custom\Controller\My'];
        yield ['Controller\MyController', 'Custom', true, true, 'Controller\My'];
    }
}
