<?php

namespace Symfony\Bundle\MakerBundle\Tests\fixtures\MakeRegistrationFormVerifyUser\tests;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserEntityTest extends TestCase
{
    public function testUserEntityHasVerifiedProperty(): void
    {
        self::assertTrue(method_exists(User::class, 'isVerified'));
        self::assertTrue(property_exists(User::class, 'isVerified'));
    }
}
