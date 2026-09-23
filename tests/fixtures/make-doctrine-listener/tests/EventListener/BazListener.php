<?php

namespace App\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventArgs;

#[AsDoctrineListener(event: 'onFoo')]
final class BazListener
{
    public function onFoo(EventArgs $event): void
    {
        // ...
    }
}
