<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class GeneratedEntityTest extends WebTestCase
{
    public function testImplementsPasswordAuthenticatedUserInterface()
    {
        $reflectedUser = new \ReflectionClass(User::class);

        self::assertTrue($reflectedUser->implementsInterface(PasswordAuthenticatedUserInterface::class));
        self::assertTrue($reflectedUser->hasMethod('getPassword'));
    }
}
