<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\FormEvents;

class FormSubscriber implements EventSubscriberInterface
{
    public function onPostSubmitEvent(PostSubmitEvent $event): void
    {
        // ...
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'onPostSubmitEvent',
        ];
    }
}
