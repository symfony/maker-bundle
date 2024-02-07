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
use Symfony\Component\Process\Process;

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

    public function testPhpCsFixerVersion(): void
    {
        $fixerPath = sprintf('%s/src/Resources/bin/php-cs-fixer-v%s.phar', \dirname(__DIR__, 2), TemplateLinter::BUNDLED_PHP_CS_FIXER_VERSION);

        $process = Process::fromShellCommandline(sprintf(
            '%s %s -V',
            str_contains(strtolower(\PHP_OS), 'win') ? 'set PHP_CS_FIXER_IGNORE_ENV=1&' : 'PHP_CS_FIXER_IGNORE_ENV=1 ',
            $fixerPath
        ));

        $process->run();

        dump([$process->getOutput(), $process->getErrorOutput()]);

        self::assertStringContainsString(TemplateLinter::BUNDLED_PHP_CS_FIXER_VERSION, $process->getOutput());
    }
}
