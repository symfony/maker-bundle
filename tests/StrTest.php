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
use Symfony\Bundle\MakerBundle\Str;

class StrTest extends TestCase
{
    /** @dataProvider provideHasSuffix */
    #[DataProvider('provideHasSuffix')]
    public function testHasSuffix($value, $suffix, $expectedResult)
    {
        $this->assertSame($expectedResult, Str::hasSuffix($value, $suffix));
    }

    /** @dataProvider provideAddSuffix */
    #[DataProvider('provideAddSuffix')]
    public function testAddSuffix($value, $suffix, $expectedResult)
    {
        $this->assertSame($expectedResult, Str::addSuffix($value, $suffix));
    }

    /** @dataProvider provideRemoveSuffix */
    #[DataProvider('provideRemoveSuffix')]
    public function testRemoveSuffix($value, $suffix, $expectedResult)
    {
        $this->assertSame($expectedResult, Str::removeSuffix($value, $suffix));
    }

    /** @dataProvider provideAsClassName */
    #[DataProvider('provideAsClassName')]
    public function testAsClassName($value, $suffix, $expectedResult)
    {
        $this->assertSame($expectedResult, Str::asClassName($value, $suffix));
    }

    /** @dataProvider provideAsTwigVariable */
    #[DataProvider('provideAsTwigVariable')]
    public function testAsTwigVariable($value, $expectedResult)
    {
        $this->assertSame($expectedResult, Str::asTwigVariable($value));
    }

    public static function provideHasSuffix()
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

    public static function provideAddSuffix()
    {
        yield ['', '', ''];
        yield ['GenerateCommand', '', 'GenerateCommand'];
        yield ['GenerateCommand', 'Command', 'GenerateCommandCommand'];
        yield ['GenerateCommand', 'command', 'GenerateCommandcommand'];
        yield ['Generatecommand', 'Command', 'GeneratecommandCommand'];
        yield ['Generatecommand', 'command', 'Generatecommandcommand'];
        yield ['GenerateCommandCommand', 'Command', 'GenerateCommandCommandCommand'];
        yield ['GenerateCommandcommand', 'Command', 'GenerateCommandcommandCommand'];
        yield ['Generate', 'command', 'Generatecommand'];
        yield ['Generate', 'Command', 'GenerateCommand'];
    }

    public static function provideRemoveSuffix()
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

    public static function provideAsClassName()
    {
        yield ['', '', ''];
        yield ['GenerateCommand', '', 'GenerateCommand'];
        yield ['Generate Command', '', 'GenerateCommand'];
        yield ['Generate-Command', '', 'GenerateCommand'];
        yield ['Generate:Command', '', 'GenerateCommand'];
        yield ['gen-erate:Co-mman-d', '', 'GenErateCoMmanD'];
        yield ['generate', 'Command', 'GenerateCommand'];
        yield ['app:generate', 'Command', 'AppGenerateCommand'];
        yield ['app:generate:command', 'Command', 'AppGenerateCommandCommand'];
    }

    public static function provideAsTwigVariable()
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
    #[DataProvider('getCamelCaseToPluralCamelCaseTests')]
    public function testCamelCaseToPluralCamelCase(string $original, string $expected)
    {
        $this->assertSame(Str::singularCamelCaseToPluralCamelCase($original), $expected);
    }

    public static function getCamelCaseToPluralCamelCaseTests()
    {
        yield ['bar', 'bars'];
        yield ['fooBar', 'fooBars'];
        yield ['FooBar', 'fooBars'];
        yield ['FooABar', 'fooABars'];
    }

    /**
     * @dataProvider getPluralCamelCaseToSingularTests
     */
    #[DataProvider('getPluralCamelCaseToSingularTests')]
    public function testPluralCamelCaseToSingular(string $original, string $expected)
    {
        $this->assertSame(Str::pluralCamelCaseToSingular($original), $expected);
    }

    public static function getPluralCamelCaseToSingularTests()
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
    #[DataProvider('getNamespaceTests')]
    public function testGetNamespace(string $fullClassName, string $expectedNamespace)
    {
        $this->assertSame($expectedNamespace, Str::getNamespace($fullClassName));
    }

    public static function getNamespaceTests()
    {
        yield ['App\\Entity\\Foo', 'App\\Entity'];
        yield ['DateTime', ''];
    }

    /**
     * @dataProvider getAsCamelCaseTests
     */
    #[DataProvider('getAsCamelCaseTests')]
    public function testAsCamelCase(string $original, string $expected)
    {
        $this->assertSame($expected, Str::asCamelCase($original));
    }

    public static function getAsCamelCaseTests()
    {
        yield ['foo', 'Foo'];

        yield ['foo_bar.baz\\pizza', 'FooBarBazPizza'];
    }

    /**
     * @dataProvider getShortClassNameCaseTests
     */
    #[DataProvider('getShortClassNameCaseTests')]
    public function testShortClassName(string $original, string $expected)
    {
        $this->assertSame($expected, Str::getShortClassName($original));
    }

    public static function getShortClassNameCaseTests()
    {
        yield ['App\\Entity\\Foo', 'Foo'];
        yield ['Foo', 'Foo'];
    }

    /**
     * @dataProvider getHumanDiscriminatorBetweenTwoClassesTests
     */
    #[DataProvider('getHumanDiscriminatorBetweenTwoClassesTests')]
    public function testHumanDiscriminatorBetweenTwoClasses(string $className, string $classNameOther, array $expected)
    {
        $this->assertSame($expected, Str::getHumanDiscriminatorBetweenTwoClasses($className, $classNameOther));
    }

    public static function getHumanDiscriminatorBetweenTwoClassesTests()
    {
        yield ['\\User', 'App\\Entity\\User', ['', 'App\\Entity']];
        yield ['App\\Entity\\User', 'App\\Entity\\Friend\\User', ['', 'Friend']];
        yield ['App\\Entity\\User', 'Custom\\Entity\\User', ['App\\Entity', 'Custom\\Entity']];
        yield ['App\\Entity\\User', 'App\\Bundle\\Entity\\User', ['Entity', 'Bundle\\Entity']];
        yield ['App\\Entity\\User', 'App\\Bundle\\User', ['Entity', 'Bundle']];
        yield ['App\\Entity\\User', 'Custom\\Bundle\\Friend\\Entity\\User', ['App\\Entity', 'Custom\\Bundle\\Friend\\Entity']];
    }

    /**
     * @dataProvider asHumanWordsTests
     */
    #[DataProvider('asHumanWordsTests')]
    public function testAsHumanWords(string $original, string $expected)
    {
        $this->assertSame($expected, Str::asHumanWords($original));
    }

    public static function asHumanWordsTests()
    {
        yield ['fooBar', 'Foo Bar'];
        yield ['FooBar', 'Foo Bar'];
        yield [' FooBar', 'Foo Bar'];
        yield [' Foo Bar ', 'Foo Bar'];
    }

    /**
     * @dataProvider provideAsRouteName
     */
    #[DataProvider('provideAsRouteName')]
    public function testAsRouteName(string $value, string $expectedRouteName)
    {
        $this->assertSame($expectedRouteName, Str::asRouteName($value));
    }

    public static function provideAsRouteName()
    {
        yield ['Example', 'app_example'];
        yield ['AppExample', 'app_example'];
        yield ['Apple', 'app_apple'];
    }
}
