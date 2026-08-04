<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class FooBarListener
{
    #[AsEventListener]
    public function onRequestEvent(RequestEvent $event): void
    {
        // ...
    }
}
