<?php

namespace App\Tests;

use App\Document\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedDocumentTest extends KernelTestCase
{
    public function testGeneratedDocument()
    {
        $user = new User();
        // bad setter should be overwritten
        $user->setFirstName('Ryan');
        $this->assertSame('Ryan', $user->getFirstName());
    }
}
