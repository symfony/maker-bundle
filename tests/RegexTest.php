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
use Symfony\Bundle\MakerBundle\Test\MakerTestEnvironment;

class RegexTest extends TestCase
{
    /** @dataProvider regexDataProvider */
    public function testRegex(string $data, array $expectedResult): void
    {
        $result = [];

        preg_match_all(MakerTestEnvironment::GENERATED_FILES_REGEX, $data, $result, \PREG_PATTERN_ORDER);

        self::assertSame($expectedResult, $result[1]);
    }

    public function regexDataProvider(): \Generator
    {
        yield 'Created Prefix' => ['created: test/something.php', ['test/something.php']];
        yield 'Updated Prefix' => ['updated: test/something.php', ['test/something.php']];
        yield 'Twig file' => ['created: test/something.html.twig', ['test/something.html.twig']];
        yield 'Config file (no dir)' => ['updated: service.yaml', ['service.yaml']];
        yield 'Line Char + 2 dir' => ['\n success\ncreated: test/somewhere/else.php\n', ['test/somewhere/else.php']];
        yield 'Multiline' => [<<< 'EOT'
            Congrats!\n
            Created: some/file.php\n
            Updated: another/config.yaml\n
            \n
            EOT,
            ['some/file.php', 'another/config.yaml'],
        ];
        yield 'Linux CI Results' => [<<< 'EOT'
            Bundled PHP-CS-Fixer & Bundled PHP-CS-Fixer Configuration\n
            \n
             created: \e]8;;file:///home/runner/work/maker-bundle/maker-bundle/tests/tmp/cache/maker_app_40cd750bba9870f18aada2478b24840a_6.4.x-dev/tests/FooBarTest.php#L1\e\tests/FooBarTest.php\e]8;;\e\\n
            \n
            EOT,
            ['tests/FooBarTest.php'],
        ];
        yield 'Windows CI Results' => [<<< 'EOT'
                Bundled PHP-CS-Fixer & Bundled PHP-CS-Fixer Configuration\r\n
                \r\n
                 created: tests/FooBarTest.php\r\n
                \r\n
            EOT,
            ['tests/FooBarTest.php'],
        ];
    }
}
