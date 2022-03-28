<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /**
     * @test
     */
    public function always_has_role_user(): void
    {
        $this->assertSame(['ROLE_USER'], (new User())->getRoles());
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], (new User())->setRoles(['ROLE_ADMIN'])->getRoles());
        $this->assertSame(
            ['ROLE_ADMIN', 'ROLE_USER'],
            (new User())->setRoles(['ROLE_ADMIN', 'ROLE_USER'])->getRoles()
        );
    }
}
