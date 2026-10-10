<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Event\PostSubmitEvent;

class FormSubscriber implements EventSubscriberInterface
{
    public function onPostSubmitEvent(PostSubmitEvent $event): void
    {
        // ...
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostSubmitEvent::class => 'onPostSubmitEvent',
        ];
    }
}
