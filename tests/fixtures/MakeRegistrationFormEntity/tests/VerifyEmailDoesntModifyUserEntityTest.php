<?php

namespace Symfony\Bundle\MakerBundle\Tests\fixtures\MakeRegistrationFormEntity\tests;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class VerifyEmailDoesntModifyUserEntityTest extends TestCase
{
    public function testVerifiedPropertyNotAddedToUserEntity(): void
    {
        self::assertFalse(method_exists(User::class, 'isVerified'));
        self::assertFalse(property_exists(User::class, 'verified'));
    }
}
