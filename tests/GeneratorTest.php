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

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;

class GeneratorTest extends TestCase
{
    /**
     * @dataProvider getClassNameDetailsTests
     */
    public function testCreateClassNameDetails(string $name, string $prefix, string $suffix, string $expectedFullClassName, string $expectedRelativeClassName): void
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

    public function getClassNameDetailsTests(): \Generator
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
}
