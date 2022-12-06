<?php

namespace Symfony\Bundle\MakerBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Util\TemplateLinter;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class TemplateLinterTest extends TestCase
{
    public function testLinterFixesPhpFile(): void
    {
        $linter = new TemplateLinter();

        $fixture = $this->copySourceToTemp('TemplateLinterController.php');

        $linter->lintPhpTemplate($fixture);

        self::assertFileEquals(__DIR__.'/fixtures/template_linter/ExpectedTemplateLinterController.php', $fixture);
    }

    /** @dataProvider pathProvider */
    public function testLinterThrowsExceptionIfBinaryOrArgumentDoNotExist(string $binaryPath, string $configPath): void
    {
        $this->expectExceptionMessage('Either the config or binary for PHP_CS_FIXER does not exist.');

        new TemplateLinter($binaryPath, $configPath);
    }

    public function pathProvider(): \Generator
    {
        yield 'Binary Path Wrong' => ['/bad/path', dirname(__DIR__, 2).'/src/Resources/config/php-cs-fixer.config.php'];
        yield 'Config Path Wrong' => [dirname(__DIR__, 2).'/src/Bin/php-cs-fixer-v3.13.0.phar', '/bad/path'];
        yield 'Both Paths Wrong' => ['/bad/path', '/bad/path'];
    }

    private function copySourceToTemp(string $sourceFileName): string
    {
        $file = new Filesystem();
        $sourcePath = __DIR__.'/fixtures/source/';
        $tmpLocation = dirname(__DIR__).'/tmp/cache/linter-test/';

        $file->copy($sourcePath.$sourceFileName, $tmpLocation.$sourceFileName);

        return $tmpLocation.$sourceFileName;
    }
}
