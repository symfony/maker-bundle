<?php

namespace App\Scheduler;

use App\Message\MessageFixture;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule]
final class MessageFixtureSchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())->add(
            // @TODO - Modify the frequency to suite your needs
            RecurringMessage::every('1 hour', new MessageFixture()),
        );
    }
}
