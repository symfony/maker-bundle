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
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassMethod;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\MethodArgument;

class ClassMethodTest extends TestCase
{
    public function testGetName()
    {
        self::assertSame('foobar', (new ClassMethod('foobar'))->getName());
    }

    /** @dataProvider returnVoidProvider */
    public function testReturnVoid(?string $returnType, bool $isVoid)
    {
        self::assertSame($isVoid, (new ClassMethod('foobar', [], $returnType))->isReturnVoid());
    }

    public function returnVoidProvider(): \Generator
    {
        yield ['void', true];
        yield ['string', false];
        yield [null, false];
    }

    public function testIsStatic()
    {
        self::assertTrue((new ClassMethod('foobar', [], null, true))->isStatic());
        self::assertFalse((new ClassMethod('foobar', [], null, false))->isStatic());
        self::assertFalse((new ClassMethod('foobar'))->isStatic());
    }

    /** @dataProvider declarationsProvider */
    public function testGetDeclaration(array $args, ?string $returnType, bool $isStatic, string $expectedDeclaration): void
    {
        $classMethod = new ClassMethod('foobar', $args, $returnType, $isStatic);

        self::assertSame($expectedDeclaration, $classMethod->getDeclaration());
    }

    public function declarationsProvider(): \Generator
    {
        yield [
            [],
            null,
            false,
            'public function foobar()',
        ];

        yield [
            [
                new MethodArgument('toto', 'array'),
                new MethodArgument('titi', 'AClass'),
                new MethodArgument('foo', 'string', '\'THE_DEFAULT_VALUE\''),
                new MethodArgument('bar', 'int', 'self::NUM'),
            ],
            'void',
            false,
            'public function foobar(array $toto, AClass $titi, string $foo = \'THE_DEFAULT_VALUE\', int $bar = self::NUM): void',
        ];

        yield [
            [],
            'string',
            true,
            'public static function foobar(): string',
        ];
    }

    /** @dataProvider argumentsUsesProvider */
    public function testGetArgumentsUse(array $args, string $expectedDeclaration): void
    {
        $classMethod = new ClassMethod('foobar', $args);

        self::assertSame($expectedDeclaration, $classMethod->getArgumentsUse());
    }

    public function argumentsUsesProvider(): \Generator
    {
        yield [
            [],
            '',
        ];

        yield [
            [
                new MethodArgument('toto', 'array'),
                new MethodArgument('titi', 'AClass'),
                new MethodArgument('foo', 'string', '\'THE_DEFAULT_VALUE\''),
                new MethodArgument('bar', 'int', 'self::NUM'),
            ],
            '$toto, $titi, $foo, $bar',
        ];

        yield [
            [
                new MethodArgument('toto', 'array'),
            ],
            '$toto',
        ];
    }
}
