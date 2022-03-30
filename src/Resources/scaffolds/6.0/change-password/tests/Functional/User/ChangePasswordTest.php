<?php

namespace App\Tests\Functional\User;

use App\Factory\UserFactory;
use App\Tests\Browser\Authentication;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ChangePasswordTest extends KernelTestCase
{
    use HasBrowser, Factories, ResetDatabase;

    /**
     * @test
     */
    public function can_change_password(): void
    {
        $user = UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);
        $currentPassword = $user->getPassword();

        $this->browser()
            ->actingAs($user->object())
            ->visit('/user/change-password')
            ->fillField('Current Password', '1234')
            ->fillField('New Password', 'new-password')
            ->fillField('Repeat New Password', 'new-password')
            ->click('Change Password')
            ->assertSuccessful()
            ->assertOn('/')
            ->assertSeeIn('.alert', 'You\'ve successfully changed your password.')
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
            ->visit('/logout')
            ->visit('/login')
            ->fillField('Email', 'mary@example.com')
            ->fillField('Password', 'new-password')
            ->click('Sign in')
            ->assertOn('/')
            ->assertSuccessful()
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
        ;

        $this->assertNotSame($currentPassword, $user->getPassword());
    }

    /**
     * @test
     */
    public function current_password_must_be_correct(): void
    {
        $user = UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);
        $currentPassword = $user->getPassword();

        $this->browser()
            ->actingAs($user->object())
            ->visit('/user/change-password')
            ->fillField('Current Password', 'invalid')
            ->fillField('New Password', 'new-password')
            ->fillField('Repeat New Password', 'new-password')
            ->click('Change Password')
            ->assertSuccessful()
            ->assertOn('/user/change-password')
            ->assertSee('This is not your current password.')
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
        ;

        $this->assertSame($currentPassword, $user->getPassword());
    }

    /**
     * @test
     */
    public function current_password_is_required(): void
    {
        $user = UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);
        $currentPassword = $user->getPassword();

        $this->browser()
            ->actingAs($user->object())
            ->visit('/user/change-password')
            ->fillField('New Password', 'new-password')
            ->fillField('Repeat New Password', 'new-password')
            ->click('Change Password')
            ->assertSuccessful()
            ->assertOn('/user/change-password')
            ->assertSee('This is not your current password.')
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
        ;

        $this->assertSame($currentPassword, $user->getPassword());
    }

    /**
     * @test
     */
    public function new_password_is_required(): void
    {
        $user = UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);
        $currentPassword = $user->getPassword();

        $this->browser()
            ->actingAs($user->object())
            ->visit('/user/change-password')
            ->fillField('Current Password', '1234')
            ->click('Change Password')
            ->assertSuccessful()
            ->assertOn('/user/change-password')
            ->assertSee('Please enter a password.')
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
        ;

        $this->assertSame($currentPassword, $user->getPassword());
    }

    /**
     * @test
     */
    public function new_passwords_must_match(): void
    {
        $user = UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);
        $currentPassword = $user->getPassword();

        $this->browser()
            ->actingAs($user->object())
            ->visit('/user/change-password')
            ->fillField('Current Password', '1234')
            ->fillField('New Password', 'new-password')
            ->fillField('Repeat New Password', 'different-new-password')
            ->click('Change Password')
            ->assertSuccessful()
            ->assertOn('/user/change-password')
            ->assertSee('The password fields must match.')
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
        ;

        $this->assertSame($currentPassword, $user->getPassword());
    }

    /**
     * @test
     */
    public function new_password_must_be_min_length(): void
    {
        $user = UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);
        $currentPassword = $user->getPassword();

        $this->browser()
            ->actingAs($user->object())
            ->visit('/user/change-password')
            ->fillField('Current Password', '1234')
            ->fillField('New Password', '4321')
            ->fillField('Repeat New Password', '4321')
            ->click('Change Password')
            ->assertSuccessful()
            ->assertOn('/user/change-password')
            ->assertSee('Your password should be at least 6 characters')
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
        ;

        $this->assertSame($currentPassword, $user->getPassword());
    }

    /**
     * @test
     */
    public function cannot_access_change_password_page_if_not_logged_in(): void
    {
        $this->browser()
            ->visit('/user/change-password')
            ->assertOn('/login')
        ;
    }
}
