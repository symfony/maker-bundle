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

use Symfony\Bundle\MakerBundle\Maker\MakeDoctrineListener;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeDoctrineListenerTest extends MakerTestCase
{
    private const EXPECTED_LISTENER_PATH = __DIR__.'/../../tests/fixtures/make-doctrine-listener/tests/EventListener/';

    private function createMakeDoctrineListenerTest(): MakerTestDetails
    {
        return $this->createMakerTest()
            ->addExtraDependencies('doctrine/orm')
        ;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_make_event_listener_without_conventional_name' => [$this->createMakeDoctrineListenerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        'foo',
                        // event name
                        'preUpdate',
                        // associate with entity?
                        'n',
                    ],
                );

                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'FooListener.php',
                    $runner->getPath('src/EventListener/FooListener.php'),
                );
            }),
        ];

        yield 'it_make_entity_listener_without_conventional_name' => [$this->createMakeDoctrineListenerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        'fooEntity',
                        // event name
                        'preUpdate',
                        // associate with entity?
                        'y',
                        // entity name
                        'User',
                    ],
                );

                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'FooEntityListener.php',
                    $runner->getPath('src/EventListener/FooEntityListener.php'),
                );
            }),
        ];

        yield 'it_makes_event_listener_for_known_event' => [$this->createMakeDoctrineListenerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'FooListener',
                        // event name
                        'preUpdate',
                        // associate with entity?
                        'n',
                    ],
                );

                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'FooListener.php',
                    $runner->getPath('src/EventListener/FooListener.php'),
                );
            }),
        ];

        yield 'it_makes_entity_listener_for_known_event' => [$this->createMakeDoctrineListenerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'FooEntityListener',
                        // event name
                        'preUpdate',
                        // associate with entity?
                        'y',
                        // entity name
                        'User',
                    ],
                );

                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'FooEntityListener.php',
                    $runner->getPath('src/EventListener/FooEntityListener.php'),
                );
            }),
        ];

        yield 'it_does_not_make_entity_listener_for_non_lifecycle_event' => [$this->createMakeDoctrineListenerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'BarListener',
                        // event name
                        'postFlush',
                        // associate with entity?
                        'y',
                        // entity name
                        'User',
                    ],
                );

                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'BarListener.php',
                    $runner->getPath('src/EventListener/BarListener.php'),
                );
            }),
        ];

        yield 'it_makes_event_listener_for_custom_event' => [$this->createMakeDoctrineListenerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'BazListener',
                        // event name
                        'onFoo',
                        // associate with entity?
                        'n',
                    ],
                );

                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'BazListener.php',
                    $runner->getPath('src/EventListener/BazListener.php'),
                );
            }),
        ];

        yield 'it_does_not_make_entity_listener_for_custom_event' => [$this->createMakeDoctrineListenerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'BazListener',
                        // event name
                        'onFoo',
                        // associate with entity?
                        'y',
                        // entity name
                        'User',
                    ],
                );

                self::assertFileEquals(
                    self::EXPECTED_LISTENER_PATH.'BazListener.php',
                    $runner->getPath('src/EventListener/BazListener.php'),
                );
            }),
        ];
    }

    protected function getMakerClass(): string
    {
        return MakeDoctrineListener::class;
    }
}
