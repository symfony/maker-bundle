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
use Symfony\Bundle\MakerBundle\Util\CliTools;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class CliToolsTest extends TestCase
{
    /**
     * @dataProvider cliEnvProvider
     */
    public function testCorrectPrefixReturnedBasedOnInputMethod(string $cliVersion, string $cliBinary, string $expectedPrefix): void
    {
        putenv(sprintf('%s=SYMFONY', $cliBinary));
        putenv(sprintf('%s=1.0.0', $cliVersion));

        self::assertSame($expectedPrefix, CliTools::getCommandPrefix());

        putenv($cliVersion);
        putenv($cliBinary);
    }

    public function cliEnvProvider(): \Generator
    {
        yield 'Using Symfony CLI' => ['SYMFONY_CLI_VERSION', 'SYMFONY_CLI_BINARY_NAME', 'symfony console '];
        yield 'Without Symfony CLI' => ['ARBITRARY_VERSION', 'ARBITRARY_BIN_NAME', 'php bin/console '];
    }
}
