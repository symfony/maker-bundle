<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HarnessRuntimeTest extends %extends%
{
    public function testGeneratedCode(): void
    {
%setup%
%code%
    }
}
