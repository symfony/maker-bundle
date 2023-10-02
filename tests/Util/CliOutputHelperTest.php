<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Util\CliOutputHelper;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class CliOutputHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('SYMFONY_CLI_BINARY_NAME');
        putenv('SYMFONY_CLI_VERSION');
    }

    public function testCorrectCommandPrefixReturnedWhenUsingSymfonyBinary(): void
    {
        self::assertSame('php bin/console', CliOutputHelper::getCommandPrefix());

        putenv('SYMFONY_CLI_BINARY_NAME=symfony');
        putenv('SYMFONY_CLI_VERSION=0.0.0');

        self::assertSame('symfony console', CliOutputHelper::getCommandPrefix());
    }
}
