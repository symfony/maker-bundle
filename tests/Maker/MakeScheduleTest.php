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

use Symfony\Bundle\MakerBundle\Maker\MakeSchedule;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeScheduleTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeSchedule::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_generates_a_schedule' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    '', // use default schedule name "MainSchedule"
                ]);

                $this->assertStringContainsString('Success', $output);

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-schedule/expected/DefaultScheduleEmpty.php',
                    $runner->getPath('src/Scheduler/MainSchedule.php')
                );
            }),
        ];

        yield 'it_generates_a_schedule_select_empty' => [$this->createMakerTest()
            ->preRun(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-schedule/standard_setup',
                    ''
                );
            })
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    0,  // Select "Empty Schedule"
                    'MySchedule', // Go with the default name "MainSchedule"
                ]);

                $this->assertStringContainsString('Success', $output);

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-schedule/expected/MyScheduleEmpty.php',
                    $runner->getPath('src/Scheduler/MySchedule.php')
                );
            }),
        ];

        yield 'it_generates_a_schedule_select_existing_message' => [$this->createMakerTest()
            ->preRun(function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-schedule/standard_setup',
                    ''
                );
            })
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    1,  // Select "MyMessage" from choice
                    '', // Go with the default name "MessageFixtureSchedule"
                ]);

                $this->assertStringContainsString('Success', $output);

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-schedule/expected/MyScheduleWithMessage.php',
                    $runner->getPath('src/Scheduler/MessageFixtureSchedule.php')
                );
            }),
        ];
    }
}
