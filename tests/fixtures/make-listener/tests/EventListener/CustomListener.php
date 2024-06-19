<?php

namespace App\EventListener;

use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class CustomListener
{
    #[AsEventListener(event: Generator::class)]
    public function onGenerator(Generator $event): void
    {
        // ...
    }
}
