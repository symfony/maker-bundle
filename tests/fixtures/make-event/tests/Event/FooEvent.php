<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class FooEvent extends Event
{
    public function __construct(
        public readonly int $id,
        private readonly ?string $name,
        protected readonly DateTimeInterface $createdAt,
    ) {
    }
}
