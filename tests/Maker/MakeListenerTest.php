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

use Symfony\Bundle\MakerBundle\Maker\MakeListener;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeListenerTest extends MakerTestCase
{
    private const EXPECTED_SUBSCRIBER_PATH = __DIR__.'/../../tests/fixtures/make-listener/tests/EventSubscriber/';
    private const EXPECTED_LISTENER_PATH = __DIR__.'/../../tests/fixtures/make-listener/tests/EventListener/';

    public static function getTestDetails(): \Generator
    {
        yield 'it_make_subscriber_without_conventional_name' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        'foo',
                        // event class type
                        'Subscriber',
                        // event name
                        'kernel.request',
                    ]
                );

                self::assertFileEquals(
                    self::EXPECTED_SUBSCRIBER_PATH.'FooSubscriber.php',
                    $runner->getPath('src/EventSubscriber/FooSubscriber.php')
                );
            }),
        ];

        yield 'it_make_listener_without_conventional_name' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        'foo',
                        // event class type
                        'Listener',
                        // event name
                        'kernel.request',
                    ]
                );

                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'FooListener.php',
                    $runner->getPath('src/EventListener/FooListener.php')
                );
            }),
        ];

        yield 'it_makes_subscriber_for_known_event' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // subscriber name
                        'FooBarSubscriber',
                        // event name
                        'kernel.request',
                    ]
                );

                self::assertFileEquals(
                    self::EXPECTED_SUBSCRIBER_PATH.'FooBarSubscriber.php',
                    $runner->getPath('src/EventSubscriber/FooBarSubscriber.php')
                );
            }),
        ];

        yield 'it_makes_subscriber_for_custom_event_class' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // subscriber name
                        'CustomSubscriber',
                        // event name
                        \Symfony\Bundle\MakerBundle\Generator::class,
                    ]
                );

                self::assertFileEquals(
                    self::EXPECTED_SUBSCRIBER_PATH.'CustomSubscriber.php',
                    $runner->getPath('src/EventSubscriber/CustomSubscriber.php')
                );
            }),
        ];

        yield 'it_makes_subscriber_for_unknown_event_class' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // subscriber name
                        'UnknownSubscriber',
                        // event name
                        'foo.unknown_event',
                    ]
                );

                self::assertFileEquals(
                    self::EXPECTED_SUBSCRIBER_PATH.'UnknownSubscriber.php',
                    $runner->getPath('src/EventSubscriber/UnknownSubscriber.php')
                );
            }),
        ];

        yield 'it_makes_listener_for_known_event' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'FooBarListener',
                        // event name
                        'kernel.request',
                    ]
                );

                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'FooBarListener.php',
                    $runner->getPath('src/EventListener/FooBarListener.php')
                );
            }),
        ];

        yield 'it_makes_listener_for_custom_event_class' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'CustomListener',
                        // event name
                        \Symfony\Bundle\MakerBundle\Generator::class,
                    ]
                );

                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'CustomListener.php',
                    $runner->getPath('src/EventListener/CustomListener.php')
                );
            }),
        ];

        yield 'it_makes_listener_for_unknown_event_class' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'UnknownListener',
                        // event name
                        'foo.unknown_event',
                    ]
                );

                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'UnknownListener.php',
                    $runner->getPath('src/EventListener/UnknownListener.php')
                );
            }),
        ];

        yield 'it_makes_listener_for_known_event_by_id' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'FooListener',
                        // event name
                        'kernel.request',
                        // accept the suggestion
                        'y',
                    ]
                );
                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'FooListener.php',
                    $runner->getPath('src/EventListener/FooListener.php')
                );
            }),
        ];

        yield 'it_makes_listener_for_known_event_by_short_class_name' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'BarListener',
                        // event name
                        'RequestEvent',
                        // accept the suggestion
                        'y',
                    ]
                );
                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'BarListener.php',
                    $runner->getPath('src/EventListener/BarListener.php')
                );
            }),
        ];

        yield 'it_makes_listener_for_known_event_by_id_with_2_letters_typo' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'FooListener',
                        // event name
                        'kernem.reques',
                        // accept the suggestion
                        'y',
                    ]
                );
                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'FooListener.php',
                    $runner->getPath('src/EventListener/FooListener.php')
                );
            }),
        ];

        yield 'it_makes_listener_for_known_event_by_short_class_name_with_2_letters_typo' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'BarListener',
                        // event name
                        'RequstEveny',
                        // accept the suggestion
                        'y',
                    ]
                );
                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'BarListener.php',
                    $runner->getPath('src/EventListener/BarListener.php')
                );
            }),
        ];

        yield 'it_makes_subscriber_for_console_event' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // subscriber name
                        'ConsoleSubscriber',
                        // event name
                        'console.command',
                    ]
                );

                self::assertFileEquals(
                    self::EXPECTED_SUBSCRIBER_PATH.'ConsoleSubscriber.php',
                    $runner->getPath('src/EventSubscriber/ConsoleSubscriber.php')
                );
            }),
        ];

        yield 'it_makes_subscriber_for_form_event' => [self::buildMakerTest()
            ->addExtraDependencies('symfony/form')
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // subscriber name
                        'FormSubscriber',
                        // event name
                        'form.post_submit',
                    ]
                );

                self::assertFileEquals(
                    self::EXPECTED_SUBSCRIBER_PATH.'FormSubscriber.php',
                    $runner->getPath('src/EventSubscriber/FormSubscriber.php')
                );
            }),
        ];

        yield 'it_makes_listener_for_form_event' => [self::buildMakerTest()
            ->addExtraDependencies('symfony/form')
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'FormListener',
                        // event name
                        'form.post_submit',
                    ]
                );

                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'FormListener.php',
                    $runner->getPath('src/EventListener/FormListener.php')
                );
            }),
        ];

        yield 'it_keeps_custom_event_name_resolved_from_listener' => [self::buildMakerTest()
            ->preRun(static function (MakerTestRunner $runner) {
                $runner->copy('make-listener/custom_event', '');
            })
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // subscriber name
                        'OrderSubscriber',
                        // event name
                        'order.placed',
                    ]
                );

                self::assertStringContainsString(
                    "'order.placed' => 'onOrderPlaced'",
                    file_get_contents($runner->getPath('src/EventSubscriber/OrderSubscriber.php'))
                );
            }),
        ];
    }

    protected function getMakerClass(): string
    {
        return MakeListener::class;
    }
}
