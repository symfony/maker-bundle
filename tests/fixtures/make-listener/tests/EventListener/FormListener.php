<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\FormEvents;

final class FormListener
{
    #[AsEventListener(event: FormEvents::POST_SUBMIT)]
    public function onPostSubmitEvent(PostSubmitEvent $event): void
    {
        // ...
    }
}
