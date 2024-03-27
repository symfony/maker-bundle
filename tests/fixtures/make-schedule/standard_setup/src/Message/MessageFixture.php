<?php

namespace App\Message;

final class MessageFixture
{
    public function __construct(
        public string $message = 'Howdy!',
    ) {
    }
}
