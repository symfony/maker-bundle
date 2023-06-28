<?php

namespace App\Tests;

use App\Document\User;
use App\Document\UserAvatar;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedDocumentTest extends KernelTestCase
{
    public function testGeneratedDocument()
    {
        self::bootKernel();

        // sanity checks to make sure the methods/classes regenerated
        $user = new User();
        $avatar = new UserAvatar();
        $user->addAvatar($avatar);

        $this->assertSame($user, $avatar->getUser());
    }
}
