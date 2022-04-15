<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

class HomepageTest extends KernelTestCase
{
    use HasBrowser;

    public function testVisitHomepage(): void
    {
        $this->browser()
            ->visit('/')
            ->assertSuccessful()
        ;
    }
}
