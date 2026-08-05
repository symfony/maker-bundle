<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class UnknownListener
{
    #[AsEventListener(event: 'foo.unknown_event')]
    public function onFooUnknownEvent($event): void
    {
        // ...
    }
}
