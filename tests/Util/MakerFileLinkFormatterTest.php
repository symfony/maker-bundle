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
use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
use Symfony\Component\ErrorHandler\ErrorRenderer\FileLinkFormatter;
use Symfony\Component\HttpKernel\Debug\FileLinkFormatter as LegacyFileLinkFormatter;

final class MakerFileLinkFormatterTest extends TestCase
{
    public function provideMakeLinkedPath(): \Generator
    {
        yield 'no_formatter' => [false, false, './my/relative/path'];
        yield 'with_formatter' => [
            true,
            true,
            "\033]8;;subl://open?url=file:///my/absolute/path&line=1\033\\./my/relative/path\033]8;;\033\\",
        ];
        yield 'formatter_returns_false' => [true, false, './my/relative/path'];
    }

    /**
     * @dataProvider provideMakeLinkedPath
     */
    public function testMakeLinkedPath(bool $withFileLinkFormatter, bool $linkFormatterReturnsLink, string $expectedOutput): void
    {
        if (getenv('MAKER_DISABLE_FILE_LINKS')) {
            $this->markTestSkipped();
        }

        $fileLinkFormatter = null;
        if ($withFileLinkFormatter) {
            if (class_exists(FileLinkFormatter::class)) {
                $fileLinkFormatter = $this->createMock(FileLinkFormatter::class);
            } else {
                $fileLinkFormatter = $this->createMock(LegacyFileLinkFormatter::class);
            }

            $return = $linkFormatterReturnsLink ? $this->returnCallback(function ($path, $line) {
                return \sprintf('subl://open?url=file://%s&line=%d', $path, $line);
            }) : $this->returnValue(false);
            $fileLinkFormatter
               ->method('format')
               ->will($return);
        }

        $sut = new MakerFileLinkFormatter($fileLinkFormatter);
        $this->assertEquals(
            $expectedOutput,
            $sut->makeLinkedPath('/my/absolute/path', './my/relative/path')
        );
    }
}
