<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedEntityTest extends KernelTestCase
{
    public function testGeneratedEntity()
    {
        $user = new User();
        // bad setter should be overwritten
        $user->setFirstName('Ryan');
        $this->assertSame('Ryan', $user->getFirstName());
    }
}
