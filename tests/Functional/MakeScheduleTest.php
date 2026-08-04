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

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\MakerBundle\Maker\MakeSchedule;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

/**
 * @group legacy
 */
#[Group('legacy')]
#[MakerTest(maker: MakeSchedule::class, profile: Profiles::MEGA)]
final class MakeScheduleTest extends MakerTestCase
{
    #[LegacyCase('MakeScheduleTest::it_generates_a_schedule_with_transport_name')]
    public function testItGeneratesAScheduleWithTransportName()
    {
        $this->deleteRecipeDefaultSchedule();

        $this->app->runMaker()
            ->answer('What should we call the new transport', 'dummy')
            ->acceptDefault('What should we call the new schedule')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('src/Scheduler/MainSchedule.php');

        $this->app->assertFileMatchesFixture('src/Scheduler/MainSchedule.php', 'expected/DefaultScheduleWithTransportName.php');

        $this->assertScheduleExecutes('MainSchedule');
    }

    #[LegacyCase('MakeScheduleTest::it_generates_a_schedule')]
    public function testItGeneratesASchedule()
    {
        $this->deleteRecipeDefaultSchedule();

        $this->app->runMaker()
            ->acceptDefault('What should we call the new transport')
            ->acceptDefault('What should we call the new schedule')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('src/Scheduler/MainSchedule.php');

        $this->app->assertFileMatchesFixture('src/Scheduler/MainSchedule.php', 'expected/DefaultScheduleEmpty.php');

        $this->assertScheduleExecutes('MainSchedule');
    }

    #[LegacyCase('MakeScheduleTest::it_generates_a_schedule_select_empty')]
    public function testItGeneratesAScheduleSelectEmpty()
    {
        $this->app->copyFixture('standard_setup', '');

        $this->deleteRecipeDefaultSchedule();

        $this->app->runMaker()
            ->acceptDefault('What should we call the new transport')
            ->answer('Select which message', '0')
            ->answer('What should we call the new schedule', 'MySchedule')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('src/Scheduler/MySchedule.php');

        $this->app->assertFileMatchesFixture('src/Scheduler/MySchedule.php', 'expected/MyScheduleEmpty.php');

        $this->assertScheduleExecutes('MySchedule');
    }

    #[LegacyCase('MakeScheduleTest::it_generates_a_schedule_select_existing_message')]
    public function testItGeneratesAScheduleSelectExistingMessage()
    {
        $this->app->copyFixture('standard_setup', '');

        $this->deleteRecipeDefaultSchedule();

        $this->app->runMaker()
            ->acceptDefault('What should we call the new transport')
            ->answer('Select which message', '1')
            ->acceptDefault('What should we call the new schedule')
            ->run()
            ->assertOutputContains('Success')
            ->assertCreated('src/Scheduler/MessageFixtureSchedule.php');

        $this->app->assertFileMatchesFixture('src/Scheduler/MessageFixtureSchedule.php', 'expected/MyScheduleWithMessage.php');

        $this->assertScheduleExecutes('MessageFixtureSchedule');
    }

    /**
     * The scheduler recipe ships src/Schedule.php, a provider for the schedule
     * named "default". A second default provider makes the container refuse to
     * compile, so the maker is tested against an app without it.
     */
    private function deleteRecipeDefaultSchedule(): void
    {
        $this->app->deleteFile('src/Schedule.php');
    }

    private function assertScheduleExecutes(string $shortClass): void
    {
        $this->app->assertGeneratedCodeRuns(<<<PHP
            \$schedule = new \\App\\Scheduler\\{$shortClass}(static::getContainer()->get('cache.app'));
            self::assertInstanceOf(\\Symfony\\Component\\Scheduler\\Schedule::class, \$schedule->getSchedule());
            PHP,
            covers: ["src/Scheduler/{$shortClass}.php"],
        );
    }
}
