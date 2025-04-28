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

    private function createMakeMessageTest(): MakerTestDetails
    {
        return $this->createMakerTest()
            ->preRun(function (MakerTestRunner $runner) {
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

    public function getTestDetails(): \Generator
    {
        yield 'it_generates_basic_message' => [$this->createMakeMessageTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker([
                    'SendWelcomeEmail',
                ]);

                $this->runMessageTest($runner, 'it_generates_basic_message.php');
            }),
        ];

        yield 'it_generates_message_with_transport' => [$this->createMakeMessageTest()
            ->run(function (MakerTestRunner $runner) {
                $this->configureTransports($runner);

                $output = $runner->runMaker([
                    'SendWelcomeEmail',
                    1,
                ]);

                $this->assertStringContainsString('Success', $output);

                $this->runMessageTest($runner, 'it_generates_message_with_transport.php');

                $messageContents = file_get_contents($runner->getPath('src/Message/SendWelcomeEmail.php'));

                if (!str_contains($messageContents, AsMessage::class)) {
                    /* @legacy remove when AsMessage is always available */
                    $messengerConfig = $runner->readYaml('config/packages/messenger.yaml');
                    $this->assertArrayHasKey('routing', $messengerConfig['framework']['messenger']);
                    $this->assertArrayHasKey('App\Message\SendWelcomeEmail', $messengerConfig['framework']['messenger']['routing']);
                    $this->assertSame(
                        'async',
                        $messengerConfig['framework']['messenger']['routing']['App\Message\SendWelcomeEmail']
                    );

                    return;
                }

                $this->assertStringContainsString(AsMessage::class, $messageContents);
                $this->assertStringContainsString("#[AsMessage('async')]", $messageContents);
            }),
        ];

        yield 'it_generates_message_with_no_transport' => [$this->createMakeMessageTest()
            ->run(function (MakerTestRunner $runner) {
                $this->configureTransports($runner);

                $output = $runner->runMaker([
                    'SendWelcomeEmail',
                    0,
                ]);

                $this->assertStringContainsString('Success', $output);

                $this->runMessageTest($runner, 'it_generates_message_with_transport.php');

                $messengerConfig = $runner->readYaml('config/packages/messenger.yaml');
                $this->assertArrayNotHasKey('routing', $messengerConfig['framework']['messenger']);

                $messageContents = file_get_contents($runner->getPath('src/Message/SendWelcomeEmail.php'));
                $this->assertStringNotContainsString(AsMessage::class, $messageContents);
            }),
        ];
    }

    private function runMessageTest(MakerTestRunner $runner, string $filename): void
    {
        $runner->copy(
            'make-message/tests/'.$filename,
            'tests/GeneratedMessageHandlerTest.php'
        );

        $runner->runTests();
    }

    private function configureTransports(MakerTestRunner $runner): void
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
