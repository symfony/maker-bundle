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
use Psr\Log\LogLevel;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Log\Logger;
use Symfony\Component\Yaml\Yaml;

class YamlSourceManipulatorTest extends TestCase
{
    /**
     * @dataProvider getYamlDataTestsUnixSlashes
     * @dataProvider getYamlDataTestsWindowsSlashes
     */
    public function testSetData(string $startingSource, array $newData, string $expectedSource)
    {
        $manipulator = new YamlSourceManipulator($startingSource);

        $logger = $this->createLogger();
        // uncomment to enhance debugging
        // $manipulator->setLogger($logger);

        $manipulator->setData($newData);

        /*
         * We technically preserve line breaks.
         * However, any added line breaks are always \n.
         * So, there's no simple way to compare the contents with
         * the line breaks, because the "expected" contents will
         * actually be a mix of the original \r\n and the new \n
         * (and so we can't use any automated method to convert
         * that and then compare)
         */
        $actualContents = $manipulator->getContents();
        $actualContents = str_replace("\r\n", "\n", $actualContents);
        $this->assertSame($expectedSource, $actualContents);
    }

    private function getYamlDataTests(): \Generator
    {
        $finder = new Finder();
        $finder->in(__DIR__.'/yaml_fixtures')
            ->files()
            ->name('*.test');

        foreach ($finder as $file) {
            [$source, $changeCode, $expected] = explode('===', $file->getContents());

            // Multiline string ends with an \n
            $source = substr_replace($source, '', \strlen($source) - 1);
            $expected = ltrim($expected, "\n");

            $data = Yaml::parse($source);
            eval($changeCode);

            yield $file->getFilename() => [
                'source' => $source,
                'newData' => $data,
                'expectedSource' => $expected,
            ];
        }

        /*
         * Known cases that are not yet supported:
         *  - Multi-line syntax with >
         *  - Shorter, deep array syntax:
         *       calls:
         *           - method: setLogger
         *             arguments: ['@logger']
         */
    }

    public function getYamlDataTestsUnixSlashes(): \Generator
    {
        foreach ($this->getYamlDataTests() as $key => $data) {
            yield 'unix_'.$key => $data;
        }
    }

    public function getYamlDataTestsWindowsSlashes(): \Generator
    {
        foreach ($this->getYamlDataTests() as $key => $data) {
            $data['source'] = str_replace("\n", "\r\n", $data['source']);

            yield 'windows_'.$key => $data;
        }
    }

    private function createLogger(): Logger
    {
        return new Logger(LogLevel::DEBUG, 'php://stdout', function (string $level, string $message, array $context) {
            $maxLen = max(array_map('strlen', array_keys($context)));

            foreach ($context as $key => $val) {
                $message .= \sprintf(
                    "\n    %s%s: %s",
                    str_repeat(' ', $maxLen - \strlen($key)),
                    $key,
                    $val
                );
            }

            return $message."\n\n";
        });
    }
}
