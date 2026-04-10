<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\NamespaceType;

class GeneratorTest extends TestCase
{
    /**
     * @dataProvider getClassNameDetailsTests
     */
    #[DataProvider('getClassNameDetailsTests')]
    public function testCreateClassNameDetails(string $name, string $prefix, string $suffix, string $expectedFullClassName, string $expectedRelativeClassName)
    {
        $fileManager = $this->createMock(FileManager::class);
        $fileManager->expects($this->any())
            ->method('getNamespacePrefixForClass')
            ->willReturn('Foo');

        $generator = new Generator($fileManager, 'App\\');

        $classNameDetails = $generator->createClassNameDetails($name, $prefix, $suffix);

        $this->assertSame($expectedFullClassName, $classNameDetails->getFullName());
        $this->assertSame($expectedRelativeClassName, $classNameDetails->getRelativeName());
    }

    public static function getClassNameDetailsTests(): \Generator
    {
        yield 'simple_class' => [
            'foo',
            'Controller\\',
            '',
            'App\\Controller\\Foo',
            'Foo',
        ];

        yield 'with_suffix' => [
            'foo',
            'Controller',
            'Controller',
            'App\\Controller\\FooController',
            'FooController',
        ];

        yield 'custom_class' => [
            '\Foo\Bar\Baz',
            'Controller',
            '',
            'Foo\Bar\Baz',
            'Bar\Baz',
        ];

        yield 'custom_class_with_suffix' => [
            '\Foo\Bar\Baz',
            'Controller',
            'Controller',
            'Foo\Bar\Baz',
            'Bar\Baz',
        ];

        yield 'enty_fqcn' => [
            '\\App\\Entity\\User',
            'Entity\\',
            '',
            'App\\Entity\\User',
            'User',
        ];

        yield 'non_prefixed_fake_fqcn' => [
            'App\\Entity\\User',
            '',
            '',
            'App\\App\\Entity\\User',
            'Entity\\User',
        ];

        yield 'real_fqcn_with_suffix' => [
            'Symfony\\Bundle\\MakerBundle\\Tests\\Generator',
            'Test',
            'Test',
            'Symfony\\Bundle\\MakerBundle\\Tests\\GeneratorTest',
            'Symfony\\Bundle\\MakerBundle\\Tests\\GeneratorTest',
        ];

        yield 'real_fqcn_without_suffix' => [
            'Symfony\\Bundle\\MakerBundle\\Tests\\GeneratorTest',
            '',
            '',
            'Symfony\\Bundle\\MakerBundle\\Tests\\GeneratorTest',
            'Symfony\\Bundle\\MakerBundle\\Tests\\GeneratorTest',
        ];
    }

    public function testGetNamespaceWithConfiguredValue()
    {
        $fileManager = $this->createMock(FileManager::class);
        $generator = new Generator($fileManager, 'App\\', null, null, [
            'entity' => 'Domain\\Entity',
            'controller' => 'Application\\Controller',
        ]);

        $this->assertSame('Domain\\Entity', $generator->getNamespace(NamespaceType::Entity));
        $this->assertSame('Application\\Controller', $generator->getNamespace(NamespaceType::Controller));
    }

    public function testGetNamespaceFallsBackToDefault()
    {
        $fileManager = $this->createMock(FileManager::class);
        $generator = new Generator($fileManager, 'App\\');

        $this->assertSame('Entity', $generator->getNamespace(NamespaceType::Entity));
        $this->assertSame('Command', $generator->getNamespace(NamespaceType::Command));
    }

    public function testCreateClassNameDetailsWithConfiguredNamespace()
    {
        $fileManager = $this->createMock(FileManager::class);
        $fileManager->expects($this->any())
            ->method('getNamespacePrefixForClass')
            ->willReturn('Foo');

        $generator = new Generator($fileManager, 'App\\', null, null, [
            'entity' => 'Domain\\Entity',
        ]);

        $entityNamespace = $generator->getNamespace(NamespaceType::Entity).'\\';
        $classNameDetails = $generator->createClassNameDetails('User', $entityNamespace);

        $this->assertSame('App\\Domain\\Entity\\User', $classNameDetails->getFullName());
    }
}
