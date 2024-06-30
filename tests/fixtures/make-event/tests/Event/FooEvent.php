<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class FooEvent extends Event
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
    ) {
    }
}
