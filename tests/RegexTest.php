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
use Symfony\Bundle\MakerBundle\Maker\MakeWebhook;
use Symfony\Bundle\MakerBundle\Test\MakerTestEnvironment;

/**
 * Common class for testing regex's used in MakerBundle.
 *
 * Create a new test method and dataProvider to test regex
 * expressions introduced in MakerBundle
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class RegexTest extends TestCase
{
    /** @dataProvider generatedFilesRegexDataProvider */
    public function testMakerTestEnvironmentGeneratedFilesRegex(string $subjectData, array $expectedResult): void
    {
        $result = [];

        preg_match_all(MakerTestEnvironment::GENERATED_FILES_REGEX, $subjectData, $result, \PREG_PATTERN_ORDER);

        self::assertSame($expectedResult, $result[1]);
    }

    private function generatedFilesRegexDataProvider(): \Generator
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

    /** @dataProvider webhookNameRegexDataProvider */
    public function testWebhookNameRegex(string $subjectData, bool $expectedResult): void
    {
        $result = preg_match(MakeWebhook::WEBHOOK_NAME_PATTERN, $subjectData);

        self::assertSame($expectedResult, (bool) $result);
    }

    private function webhookNameRegexDataProvider(): \Generator
    {
        // Valid cases
        yield 'Simple word' => ['mywebhook', true];
        yield 'With underscore' => ['my_webhook', true];
        yield 'With hyphen' => ['my-webhook', true];
        yield 'With extend ascii chars' => ['éÿù', true];
        yield 'With numbers' => ['mywebh00k', true];

        // Invalid cases
        yield 'Leading number' => ['1mywebh00k', false];
        yield 'With space' => ['my webhook', false];
        yield 'With non-ascii characters' => ['web🪝', false];
    }
}
