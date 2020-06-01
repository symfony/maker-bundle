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
use Symfony\Bundle\MakerBundle\MakerArgument;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
final class MakerArgumentTest extends TestCase
{
    public function dataProvider(): \Generator
    {
        yield ['', true];
        yield [null, true];
        yield [false, false];
        yield ['false', false];
        yield [0, false];
        yield ['some-value', false];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testIsEmpty($value, bool $expected): void
    {
        $argument = new MakerArgument('test', $value);

        $this->assertSame($expected, $argument->isEmpty());
    }
}
