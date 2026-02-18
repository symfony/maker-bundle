<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, entity: User::class)]
final class FooEntityListener
{
    public function __invoke(User $entity, PreUpdateEventArgs $event): void
    {
        // ...
    }
}
