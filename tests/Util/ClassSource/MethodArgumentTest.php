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
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\MethodArgument;

/**
 * Class MethodArgumentTest.
 *
 * @author Benjamin Georgeault
 */
class MethodArgumentTest extends TestCase
{
    /** @dataProvider declarationsProvider */
    public function testGetDeclaration(?string $type, ?string $default, string $expected)
    {
        $methodArgument = new MethodArgument('foo', $type, $default);

        $this->assertSame($expected, $methodArgument->getDeclaration());
    }

    public function declarationsProvider(): \Generator
    {
        yield [
            null,
            null,
            '$foo',
        ];

        yield [
            'string',
            '\'foobar\'',
            'string $foo = \'foobar\'',
        ];

        yield [
            'string',
            null,
            'string $foo',
        ];
    }

    public function testGetVariable()
    {
        $methodArgument = new MethodArgument('foo');

        $this->assertSame('$foo', $methodArgument->getVariable());
    }
}
