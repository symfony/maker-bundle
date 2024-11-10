<?php

namespace App\Service;

class MultipleImpService implements FooInterface, BarInterface
{
    public function getTheValue(): string
    {
        return 'THE_FOO_VALUE';
    }

    public function doSomething(): void
    {
    }
}
