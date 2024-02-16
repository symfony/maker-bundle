<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class BarListener
{
    #[AsEventListener(event: RequestEvent::class)]
    public function onRequestEvent(RequestEvent $event): void
    {
        // ...
    }
}
