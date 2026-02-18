<?php

namespace App\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postFlush)]
final class BarListener
{
    public function postFlush(PostFlushEventArgs $event): void
    {
        // ...
    }
}
