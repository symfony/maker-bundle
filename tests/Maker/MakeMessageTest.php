<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeMessage;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\Messenger\Attribute\AsMessage;
use Symfony\Component\Yaml\Yaml;

class MakeMessageTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeMessage::class;
    }

    private static function createMakeMessageTest(): MakerTestDetails
    {
        return self::buildMakerTest()
            ->preRun(static function (MakerTestRunner $runner) {
                $runner->writeFile(
                    'config/services_test.yaml',
                    Yaml::dump([
                        'services' => [
                            '_defaults' => ['public' => true],
                            'test.message_bus' => '@messenger.bus.default',
                        ],
                    ])
                );
            });
    }

    public static function getTestDetails(): \Generator
    {
        yield 'it_generates_basic_message' => [self::createMakeMessageTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([
                    'SendWelcomeEmail',
                ]);

                self::runMessageTest($runner, 'it_generates_basic_message.php');
            }),
        ];

        yield 'it_generates_message_with_transport' => [self::createMakeMessageTest()
            ->run(static function (MakerTestRunner $runner) {
                self::configureTransports($runner);

                $output = $runner->runMaker([
                    'SendWelcomeEmail',
                    1,
                ]);

                self::assertStringContainsString('Success', $output);

                self::runMessageTest($runner, 'it_generates_message_with_transport.php');

                $messageContents = file_get_contents($runner->getPath('src/Message/SendWelcomeEmail.php'));

                self::assertStringContainsString(AsMessage::class, $messageContents);
                self::assertStringContainsString("#[AsMessage('async')]", $messageContents);
            }),
        ];

        yield 'it_generates_message_with_no_transport' => [self::createMakeMessageTest()
            ->run(static function (MakerTestRunner $runner) {
                self::configureTransports($runner);

                $output = $runner->runMaker([
                    'SendWelcomeEmail',
                    0,
                ]);

                self::assertStringContainsString('Success', $output);

                self::runMessageTest($runner, 'it_generates_message_with_transport.php');

                $messengerConfig = $runner->readYaml('config/packages/messenger.yaml');
                self::assertArrayNotHasKey('routing', $messengerConfig['framework']['messenger']);

                $messageContents = file_get_contents($runner->getPath('src/Message/SendWelcomeEmail.php'));
                self::assertStringNotContainsString(AsMessage::class, $messageContents);
            }),
        ];

        yield 'it_generates_basic_message_non_interactively' => [self::createMakeMessageTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([], 'SendWelcomeEmail --no-interaction');

                self::runMessageTest($runner, 'it_generates_basic_message.php');
            }),
        ];

        yield 'it_generates_message_with_transport_non_interactively' => [self::createMakeMessageTest()
            ->run(static function (MakerTestRunner $runner) {
                self::configureTransports($runner);

                $output = $runner->runMaker([], 'SendWelcomeEmail --no-interaction --transport=async');

                self::assertStringContainsString('Success', $output);

                self::runMessageTest($runner, 'it_generates_message_with_transport.php');

                $messageContents = file_get_contents($runner->getPath('src/Message/SendWelcomeEmail.php'));

                self::assertStringContainsString(AsMessage::class, $messageContents);
                self::assertStringContainsString("#[AsMessage('async')]", $messageContents);
            }),
        ];

        yield 'it_generates_message_with_no_transport_non_interactively' => [self::createMakeMessageTest()
            ->run(static function (MakerTestRunner $runner) {
                self::configureTransports($runner);

                $output = $runner->runMaker([], 'SendWelcomeEmail --no-interaction');

                self::assertStringContainsString('Success', $output);

                self::runMessageTest($runner, 'it_generates_message_with_transport.php');

                $messageContents = file_get_contents($runner->getPath('src/Message/SendWelcomeEmail.php'));
                self::assertStringNotContainsString(AsMessage::class, $messageContents);
            }),
        ];

        yield 'it_rejects_unknown_transport_non_interactively' => [self::createMakeMessageTest()
            ->run(static function (MakerTestRunner $runner) {
                self::configureTransports($runner);

                $output = $runner->runMaker([], 'SendWelcomeEmail --no-interaction --transport=does_not_exist', allowedToFail: true);

                self::assertStringContainsString('is not configured', $output);
                self::assertFileDoesNotExist($runner->getPath('src/Message/SendWelcomeEmail.php'));
            }),
        ];
    }

    private static function runMessageTest(MakerTestRunner $runner, string $filename): void
    {
        $runner->copy(
            'make-message/tests/'.$filename,
            'tests/GeneratedMessageHandlerTest.php'
        );

        $runner->runTests();
    }

    private static function configureTransports(MakerTestRunner $runner): void
    {
        $runner->writeFile(
            'config/packages/messenger.yaml',
            <<<EOF
                framework:
                    messenger:
                        transports:
                            async: 'sync://'
                            async_high_priority: 'sync://'
                EOF
        );
    }
}
