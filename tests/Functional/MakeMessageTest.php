<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Functional;

use Symfony\Bundle\MakerBundle\Maker\MakeMessage;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;
use Symfony\Component\Messenger\Attribute\AsMessage;

#[MakerTest(maker: MakeMessage::class, profile: Profiles::MEGA)]
final class MakeMessageTest extends MakerTestCase
{
    #[LegacyCase('MakeMessageTest::it_generates_basic_message')]
    public function testItGeneratesBasicMessage()
    {
        $this->exposeMessageBus();

        $this->app->runMaker()
            ->answer('The name of the message class', 'SendWelcomeEmail')
            // the skeleton recipe ships a sync transport, so this is always asked
            ->acceptDefault('Which transport do you want to route your message to')
            ->run()
            ->assertCreated(
                'src/Message/SendWelcomeEmail.php',
                'src/MessageHandler/SendWelcomeEmailHandler.php',
            );

        $this->runMessageTest('it_generates_basic_message.php');
    }

    #[LegacyCase('MakeMessageTest::it_generates_message_with_transport')]
    public function testItGeneratesMessageWithTransport()
    {
        $this->exposeMessageBus();
        $this->configureTransports();

        $this->app->runMaker()
            ->answer('The name of the message class', 'SendWelcomeEmail')
            ->answer('Which transport do you want to route your message to', '1')
            ->run()
            ->assertOutputContains('Success');

        $this->runMessageTest('it_generates_message_with_transport.php');

        $messageContents = $this->app->readFile('src/Message/SendWelcomeEmail.php');

        if (!str_contains($messageContents, AsMessage::class)) {
            /* @legacy remove when AsMessage is always available */
            $messengerConfig = $this->app->readYaml('config/packages/messenger.yaml');
            self::assertSame('async', $messengerConfig['framework']['messenger']['routing']['App\Message\SendWelcomeEmail'] ?? null);

            return;
        }

        self::assertStringContainsString(AsMessage::class, $messageContents);
        self::assertStringContainsString("#[AsMessage('async')]", $messageContents);
    }

    #[LegacyCase('MakeMessageTest::it_generates_message_with_no_transport')]
    public function testItGeneratesMessageWithNoTransport()
    {
        $this->exposeMessageBus();
        $this->configureTransports();

        $this->app->runMaker()
            ->answer('The name of the message class', 'SendWelcomeEmail')
            ->answer('Which transport do you want to route your message to', '0')
            ->run()
            ->assertOutputContains('Success');

        $this->runMessageTest('it_generates_message_with_transport.php');

        $messengerConfig = $this->app->readYaml('config/packages/messenger.yaml');
        self::assertArrayNotHasKey('routing', $messengerConfig['framework']['messenger']);

        $this->app->assertFileNotContains('src/Message/SendWelcomeEmail.php', AsMessage::class);
    }

    private function runMessageTest(string $fixtureTestFile): void
    {
        // dispatching through a real (sync) bus executes both the message and
        // its handler
        $this->app->runGeneratedTests($fixtureTestFile, covers: [
            'src/Message/SendWelcomeEmail.php',
            'src/MessageHandler/SendWelcomeEmailHandler.php',
        ]);
    }

    private function exposeMessageBus(): void
    {
        $this->app->writeYaml('config/services_test.yaml', [
            'services' => [
                '_defaults' => ['public' => true],
                'test.message_bus' => '@messenger.bus.default',
            ],
        ]);
    }

    private function configureTransports(): void
    {
        $this->app->writeYaml('config/packages/messenger.yaml', [
            'framework' => [
                'messenger' => [
                    'transports' => [
                        'async' => 'sync://',
                        'async_high_priority' => 'sync://',
                    ],
                ],
            ],
        ]);
    }
}
