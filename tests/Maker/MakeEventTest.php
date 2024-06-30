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

use Symfony\Bundle\MakerBundle\Maker\MakeEvent;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeEventTest extends MakerTestCase
{
    private const EXPECTED_EVENT_PATH = __DIR__.'/../../tests/fixtures/make-event/tests/Event/';
    protected function getMakerClass(): string
    {
        return MakeEvent::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_makes_event' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // event class name
                        'FooEvent',
                        // first property name
                        'id',
                        // first property type
                        'int',
                        // first property nullable
                        'no',
                        // second property name
                        'name',
                        // second property type
                        'string',
                        // second property nullable
                        'yes',
                        '',
                    ]
                );

                self::assertFileEquals(
                    self::EXPECTED_EVENT_PATH.'FooEvent.php',
                    $runner->getPath('src/Event/FooEvent.php')
                );
            }),
        ];
    }
}
