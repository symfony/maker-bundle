<?php

namespace App\Tests\Functional;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ChangePasswordTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use ResetDatabase;

    public function testCanChangePassword(): void
    {
        $user = UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);
        $currentPassword = $user->getPassword();

        $this->browser()
            ->actingAs($user->object())
            ->visit('/user/change-password')
            ->fillField('Current Password', '1234')
            ->fillField('New Password', 'new-password')
            ->click('Change Password')
            ->assertSuccessful()
            ->assertOn('/')
            ->assertSeeIn('.alert', 'You\'ve successfully changed your password.')
            ->assertAuthenticated('mary@example.com')
            ->visit('/logout')
            ->visit('/login')
            ->fillField('Email', 'mary@example.com')
            ->fillField('Password', 'new-password')
            ->click('Sign in')
            ->assertOn('/')
            ->assertSuccessful()
            ->assertAuthenticated('mary@example.com')
        ;

        $this->assertNotSame($currentPassword, $user->getPassword());
    }

    public function testCurrentPasswordMustBeCorrect(): void
    {
        $user = UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);
        $currentPassword = $user->getPassword();

        $this->browser()
            ->actingAs($user->object())
            ->visit('/user/change-password')
            ->fillField('Current Password', 'invalid')
            ->fillField('New Password', 'new-password')
            ->click('Change Password')
            ->assertStatus(422)
            ->assertOn('/user/change-password')
            ->assertSee('This is not your current password.')
            ->assertAuthenticated('mary@example.com')
        ;

        $this->assertSame($currentPassword, $user->getPassword());
    }

    public function testCurrentPasswordIsRequired(): void
    {
        $user = UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);
        $currentPassword = $user->getPassword();

        $this->browser()
            ->actingAs($user->object())
            ->visit('/user/change-password')
            ->fillField('New Password', 'new-password')
            ->click('Change Password')
            ->assertStatus(422)
            ->assertOn('/user/change-password')
            ->assertSee('This is not your current password.')
            ->assertAuthenticated('mary@example.com')
        ;

        $this->assertSame($currentPassword, $user->getPassword());
    }

    public function testNewPasswordIsRequired(): void
    {
        $user = UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);
        $currentPassword = $user->getPassword();

        $this->browser()
            ->actingAs($user->object())
            ->visit('/user/change-password')
            ->fillField('Current Password', '1234')
            ->click('Change Password')
            ->assertStatus(422)
            ->assertOn('/user/change-password')
            ->assertSee('Please enter a password.')
            ->assertAuthenticated('mary@example.com')
        ;

        $this->assertSame($currentPassword, $user->getPassword());
    }

    public function testCannotAccessChangePasswordPageIfNotLoggedIn(): void
    {
        $this->browser()
            ->visit('/user/change-password')
            ->assertOn('/login')
        ;
    }
}