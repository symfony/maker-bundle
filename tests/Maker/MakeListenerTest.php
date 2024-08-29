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

    public function getTestDetails(): \Generator
    {
        yield 'it_make_subscriber_without_conventional_name' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
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

        yield 'it_make_listener_without_conventional_name' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
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

        yield 'it_makes_subscriber_for_known_event' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
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

        yield 'it_makes_subscriber_for_custom_event_class' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
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

        yield 'it_makes_subscriber_for_unknown_event_class' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
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

        yield 'it_makes_listener_for_known_event' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
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

        yield 'it_makes_listener_for_custom_event_class' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
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

        yield 'it_makes_listener_for_unknown_event_class' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
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

        yield 'it_makes_listener_for_known_event_by_id' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
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

        yield 'it_makes_listener_for_known_event_by_short_class_name' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
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

        yield 'it_makes_listener_for_known_event_by_id_with_2_letters_typo' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
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

        yield 'it_makes_listener_for_known_event_by_short_class_name_with_2_letters_typo' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
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
    }

    protected function getMakerClass(): string
    {
        return MakeListener::class;
    }
}
