<?php

namespace App\EventListener;

use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class CustomListener
{
    #[AsEventListener]
    public function onGenerator(Generator $event): void
    {
        // ...
    }
}
