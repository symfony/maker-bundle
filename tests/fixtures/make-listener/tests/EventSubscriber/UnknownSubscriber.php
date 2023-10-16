<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UnknownSubscriber implements EventSubscriberInterface
{
    public function onFooUnknownEvent($event): void
    {
        // ...
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'foo.unknown_event' => 'onFooUnknownEvent',
        ];
    }
}
