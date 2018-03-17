<?php

namespace Symfony\Bundle\MakerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Str;

class StrTest extends TestCase
{
    /** @dataProvider provideHasSuffix */
    public function testHasSuffix($value, $suffix, $expectedResult)
    {
        $this->assertSame($expectedResult, Str::hasSuffix($value, $suffix));
    }

    /** @dataProvider provideAddSuffix */
    public function testAddSuffix($value, $suffix, $expectedResult)
    {
        $this->assertSame($expectedResult, Str::addSuffix($value, $suffix));
    }

    /** @dataProvider provideRemoveSuffix */
    public function testRemoveSuffix($value, $suffix, $expectedResult)
    {
        $this->assertSame($expectedResult, Str::removeSuffix($value, $suffix));
    }

    /** @dataProvider provideAsClassName */
    public function testAsClassName($value, $suffix, $expectedResult)
    {
        $this->assertSame($expectedResult, Str::asClassName($value, $suffix));
    }

    /** @dataProvider provideAsTwigVariable */
    public function testAsTwigVariable($value, $expectedResult)
    {
        $this->assertSame($expectedResult, Str::asTwigVariable($value));
    }

    public function provideHasSuffix()
    {
        yield ['', '', true];
        yield ['GenerateCommand', '', false];
        yield ['GenerateCommand', 'Command', true];
        yield ['GenerateCommand', 'command', true];
        yield ['Generatecommand', 'Command', true];
        yield ['Generatecommand', 'command', true];
        yield ['Generate', 'command', false];
        yield ['Generate', 'Command', false];
    }

    public function provideAddSuffix()
    {
        yield ['', '', ''];
        yield ['GenerateCommand', '', 'GenerateCommand'];
        yield ['GenerateCommand', 'Command', 'GenerateCommand'];
        yield ['GenerateCommand', 'command', 'Generatecommand'];
        yield ['Generatecommand', 'Command', 'GenerateCommand'];
        yield ['Generatecommand', 'command', 'Generatecommand'];
        yield ['GenerateCommandCommand', 'Command', 'GenerateCommandCommand'];
        yield ['GenerateCommandcommand', 'Command', 'GenerateCommandCommand'];
        yield ['Generate', 'command', 'Generatecommand'];
        yield ['Generate', 'Command', 'GenerateCommand'];
    }

    public function provideRemoveSuffix()
    {
        yield ['', '', ''];
        yield ['GenerateCommand', '', 'GenerateCommand'];
        yield ['GenerateCommand', 'Command', 'Generate'];
        yield ['GenerateCommand', 'command', 'Generate'];
        yield ['Generatecommand', 'Command', 'Generate'];
        yield ['Generatecommand', 'command', 'Generate'];
        yield ['GenerateCommandCommand', 'Command', 'GenerateCommand'];
        yield ['GenerateCommandcommand', 'Command', 'GenerateCommand'];
        yield ['Generate', 'Command', 'Generate'];
    }

    public function provideAsClassName()
    {
        yield ['', '', ''];
        yield ['GenerateCommand', '', 'GenerateCommand'];
        yield ['Generate Command', '', 'GenerateCommand'];
        yield ['Generate-Command', '', 'GenerateCommand'];
        yield ['Generate:Command', '', 'GenerateCommand'];
        yield ['gen-erate:Co-mman-d', '', 'GenErateCoMmanD'];
        yield ['generate', 'Command', 'GenerateCommand'];
        yield ['app:generate', 'Command', 'AppGenerateCommand'];
        yield ['app:generate:command', 'Command', 'AppGenerateCommand'];
    }

    public function provideAsTwigVariable()
    {
        yield ['', '', ''];
        yield ['GenerateCommand', 'generate_command'];
        yield ['Generate Command', 'generate_command'];
        yield ['Generate-Command', 'generate_command'];
        yield ['Generate:Command', 'generate_command'];
        yield ['gen-erate:Co-mman-d', 'gen_erate_co_mman_d'];
        yield ['generate', 'generate'];
    }

    /**
     * @dataProvider getCamelCaseToPluralCamelCaseTests
     */
    public function testCamelCaseToPluralCamelCase(string $original, string $expected)
    {
        $this->assertSame(Str::singularCamelCaseToPluralCamelCase($original), $expected);
    }

    public function getCamelCaseToPluralCamelCaseTests()
    {
        yield ['bar', 'bars'];
        yield ['fooBar', 'fooBars'];
        yield ['FooBar', 'fooBars'];
        yield ['FooABar', 'fooABars'];
    }

    /**
     * @dataProvider getPluralCamelCaseToSingularTests
     */
    public function testPluralCamelCaseToSingular(string $original, string $expected)
    {
        $this->assertSame(Str::pluralCamelCaseToSingular($original), $expected);
    }

    public function getPluralCamelCaseToSingularTests()
    {
        yield ['bar', 'bar'];
        yield ['bars', 'bar'];
        yield ['fooBars', 'fooBar'];
        yield ['FooBars', 'fooBar'];
        yield ['FooABars', 'fooABar'];
    }

    /**
     * @dataProvider getNamespaceTests
     */
    public function testGetNamespace(string $fullClassName, string $expectedNamespace)
    {
        $this->assertSame($expectedNamespace, Str::getNamespace($fullClassName));
    }

    public function getNamespaceTests()
    {
        yield ['App\\Entity\\Foo', 'App\\Entity'];
        yield ['DateTime', ''];
    }

    /**
     * @dataProvider getAsCamelCaseTests
     */
    public function testAsCamelCase(string $original, string $expected)
    {
        $this->assertSame($expected, Str::asCamelCase($original));
    }

    public function getAsCamelCaseTests()
    {
        yield ['foo', 'Foo'];

        yield ['foo_bar.baz\\pizza', 'FooBarBazPizza'];
    }
}
