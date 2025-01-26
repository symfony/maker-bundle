<?php

namespace App\Service;

class ForExtendService implements FooInterface, BarInterface
{
    public function getTheValue(): string
    {
        return 'THE_FOO_VALUE';
    }

    public function doSomething(): void
    {
    }

    public function booh(): void
    {
    }
}
