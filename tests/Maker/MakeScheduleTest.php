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

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\MakerBundle\Maker\MakeSchedule;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

/**
 * @group legacy
 */
#[Group('legacy')]
class MakeScheduleTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeSchedule::class;
    }

    public static function getTestDetails(): \Generator
    {
        yield 'it_generates_a_schedule_with_transport_name' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    'dummy', // use transport name "dummy"
                    '', // use default schedule name "MainSchedule"
                ]);

                self::assertStringContainsString('Success', $output);

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-schedule/expected/DefaultScheduleWithTransportName.php',
                    $runner->getPath('src/Scheduler/MainSchedule.php')
                );
            }),
        ];

        yield 'it_generates_a_schedule' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    '', // use default transport name
                    '', // use default schedule name "MainSchedule"
                ]);

                self::assertStringContainsString('Success', $output);

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-schedule/expected/DefaultScheduleEmpty.php',
                    $runner->getPath('src/Scheduler/MainSchedule.php')
                );
            }),
        ];

        yield 'it_generates_a_schedule_select_empty' => [self::buildMakerTest()
            ->preRun(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-schedule/standard_setup',
                    ''
                );
            })
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    '', // Use the default transport name
                    0,  // Select "Empty Schedule"
                    'MySchedule', // Go with the default name "MainSchedule"
                ]);

                self::assertStringContainsString('Success', $output);

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-schedule/expected/MyScheduleEmpty.php',
                    $runner->getPath('src/Scheduler/MySchedule.php')
                );
            }),
        ];

        yield 'it_generates_a_schedule_select_existing_message' => [self::buildMakerTest()
            ->preRun(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-schedule/standard_setup',
                    ''
                );
            })
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    '', // Use the default transport name
                    1,  // Select "MyMessage" from choice
                    '', // Go with the default name "MessageFixtureSchedule"
                ]);

                self::assertStringContainsString('Success', $output);

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-schedule/expected/MyScheduleWithMessage.php',
                    $runner->getPath('src/Scheduler/MessageFixtureSchedule.php')
                );
            }),
        ];

        yield 'it_generates_a_schedule_non_interactively' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '--no-interaction');

                self::assertStringContainsString('Success', $output);

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-schedule/expected/DefaultScheduleEmpty.php',
                    $runner->getPath('src/Scheduler/MainSchedule.php')
                );
            }),
        ];

        yield 'it_generates_a_schedule_with_transport_name_non_interactively' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '--no-interaction --transport-name=dummy');

                self::assertStringContainsString('Success', $output);

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-schedule/expected/DefaultScheduleWithTransportName.php',
                    $runner->getPath('src/Scheduler/MainSchedule.php')
                );
            }),
        ];

        yield 'it_generates_a_schedule_with_message_non_interactively' => [self::buildMakerTest()
            ->preRun(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-schedule/standard_setup',
                    ''
                );
            })
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '--no-interaction --message=MessageFixture');

                self::assertStringContainsString('Success', $output);

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-schedule/expected/MyScheduleWithMessage.php',
                    $runner->getPath('src/Scheduler/MessageFixtureSchedule.php')
                );
            }),
        ];

        yield 'it_rejects_unknown_message_non_interactively' => [self::buildMakerTest()
            ->preRun(static function (MakerTestRunner $runner) {
                $runner->copy(
                    'make-schedule/standard_setup',
                    ''
                );
            })
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '--no-interaction --message=DoesNotExist', allowedToFail: true);

                self::assertStringContainsString('was not found', $output);
                self::assertFileDoesNotExist($runner->getPath('src/Scheduler/DoesNotExistSchedule.php'));
            }),
        ];
    }
}
