<?php

namespace Symfony\Bundle\MakerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;

class GeneratorTest extends TestCase
{
    /**
     * @dataProvider getClassNameDetailsTests
     */
    public function testCreateClassNameDetails(string $name, string $prefix, string $suffix = '', string $expectedFullClassName, string $expectedRelativeClassName)
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

    public function getClassNameDetailsTests()
    {
        yield 'simple_class' => [
            'foo',
            'Controller\\',
            '',
            'App\\Controller\\Foo',
            'Foo'
        ];

        yield 'with_suffix' => [
            'foo',
            'Controller',
            'Controller',
            'App\\Controller\\FooController',
            'FooController'
        ];

        yield 'custom_class' => [
            '\Foo\Bar\Baz',
            'Controller',
            '',
            'Foo\Bar\Baz',
            'Bar\Baz'
        ];

        yield 'custom_class_with_suffix' => [
            '\Foo\Bar\Baz',
            'Controller',
            'Controller',
            'Foo\Bar\Baz',
            'Bar\Baz'
        ];
    }
}
