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
use Symfony\Bundle\MakerBundle\Util\TemplateLinter;

/**
 * Linter tests are written in `tests/Maker/TemplateLinterTest`.
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class TemplateLinterTest extends TestCase
{
    public function testExceptionBinaryPathDoesntExist(): void
    {
        $this->expectExceptionMessage('The MAKER_PHP_CS_FIXER_BINARY_PATH provided: /some/bad/path does not exist.');

        new TemplateLinter(phpCsFixerBinaryPath: '/some/bad/path');
    }

    public function testExceptionThrownIfConfigPathDoesntExist(): void
    {
        $this->expectExceptionMessage('The MAKER_PHP_CS_FIXER_CONFIG_PATH provided: /bad/config/path does not exist.');

        new TemplateLinter(phpCsFixerConfigPath: '/bad/config/path');
    }
}
