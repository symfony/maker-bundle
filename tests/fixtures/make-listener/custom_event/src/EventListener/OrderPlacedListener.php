<?php

namespace App\EventListener;

use App\Event\OrderPlacedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class OrderPlacedListener
{
    #[AsEventListener(event: 'order.placed')]
    public function onOrderPlaced(OrderPlacedEvent $event): void
    {
    }
}
