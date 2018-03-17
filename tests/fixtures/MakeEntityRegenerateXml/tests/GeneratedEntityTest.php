<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\User;
use App\Entity\UserAvatar;

class GeneratedEntityTest extends KernelTestCase
{
    public function testGeneratedEntity()
    {
        self::bootKernel();

        // sanity checks to make sure the methods/classes regenerated
        $user = new User();
        $avatar = new UserAvatar();
        $user->addAvatar($avatar);

        $this->assertSame($user, $avatar->getUser());
    }
}
