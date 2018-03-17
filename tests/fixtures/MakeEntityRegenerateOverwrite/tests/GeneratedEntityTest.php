<?php

namespace App\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManager;
use App\Entity\User;
use App\Entity\UserAvatar;

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
