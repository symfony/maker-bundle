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
    protected function getMakerClass(): string
    {
        return MakeListener::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_makes_listener_for_known_event' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'FooBar',
                        // event name
                        'kernel.request',
                    ]
                );

                self::assertStringContainsString(
                    'KernelEvents::REQUEST',
                    file_get_contents($runner->getPath('src/EventListener/FooBarListener.php'))
                );

                self::assertStringContainsString(
                    'onKernelRequest',
                    file_get_contents($runner->getPath('src/EventListener/FooBarListener.php'))
                );
            }),
        ];

        yield 'it_makes_listener_for_custom_event_class' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'FooBar',
                        // event name
                        \Symfony\Bundle\MakerBundle\Generator::class,
                    ]
                );

                self::assertStringContainsString(
                    'Generator::class',
                    file_get_contents($runner->getPath('src/EventListener/FooBarListener.php'))
                );

                self::assertStringContainsString(
                    'onGenerator',
                    file_get_contents($runner->getPath('src/EventListener/FooBarListener.php'))
                );
            }),
        ];

        yield 'it_makes_listener_for_unknown_event_class' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // listener name
                        'FooBar',
                        // event name
                        'foo.unknown_event',
                    ]
                );

                self::assertStringContainsString(
                    '\'foo.unknown_event\'',
                    file_get_contents($runner->getPath('src/EventListener/FooBarListener.php'))
                );

                self::assertStringContainsString(
                    'onFooUnknownEvent',
                    file_get_contents($runner->getPath('src/EventListener/FooBarListener.php'))
                );
            }),
        ];
    }
}
