<?php

namespace App\EventSubscriber;

use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomSubscriber implements EventSubscriberInterface
{
    public function onGenerator(Generator $event): void
    {
        // ...
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Generator::class => 'onGenerator',
        ];
    }
}
