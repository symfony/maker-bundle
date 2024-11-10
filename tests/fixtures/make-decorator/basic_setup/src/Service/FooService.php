<?php

namespace App\Service;

class FooService implements FooInterface
{
    public function getTheValue(): string
    {
        return 'THE_FOO_VALUE';
    }
}
