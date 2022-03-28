<?php

namespace App\Tests\Functional;

use App\Factory\UserFactory;
use App\Tests\Browser\Authentication;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class AuthenticationTest extends KernelTestCase
{
    use HasBrowser, Factories, ResetDatabase;

    /**
     * @test
     */
    public function can_login_and_logout(): void
    {
        UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);

        $this->browser()
            ->use(Authentication::assertNotAuthenticated())
            ->visit('/login')
            ->fillField('Email', 'mary@example.com')
            ->fillField('Password', '1234')
            ->click('Sign in')
            ->assertOn('/')
            ->assertSuccessful()
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
            ->visit('/logout')
            ->assertOn('/')
            ->use(Authentication::assertNotAuthenticated())
        ;
    }

    /**
     * @test
     */
    public function login_with_target(): void
    {
        UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);

        $this->browser()
            ->use(Authentication::assertNotAuthenticated())
            ->visit('/login?target=/some/page')
            ->fillField('Email', 'mary@example.com')
            ->fillField('Password', '1234')
            ->click('Sign in')
            ->assertOn('/some/page')
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
        ;
    }

    /**
     * @test
     */
    public function login_with_invalid_password(): void
    {
        UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);

        $this->browser()
            ->visit('/login')
            ->fillField('Email', 'mary@example.com')
            ->fillField('Password', 'invalid')
            ->click('Sign in')
            ->assertOn('/login')
            ->assertSuccessful()
            ->assertFieldEquals('Email', 'mary@example.com')
            ->assertSee('Invalid credentials.')
            ->use(Authentication::assertNotAuthenticated())
        ;
    }

    /**
     * @test
     */
    public function login_with_invalid_email(): void
    {
        $this->browser()
            ->visit('/login')
            ->fillField('Email', 'invalid@example.com')
            ->fillField('Password', '1234')
            ->click('Sign in')
            ->assertOn('/login')
            ->assertSuccessful()
            ->assertFieldEquals('Email', 'invalid@example.com')
            ->assertSee('Invalid credentials.')
            ->use(Authentication::assertNotAuthenticated())
        ;
    }

    /**
     * @test
     */
    public function login_with_invalid_csrf(): void
    {
        UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);

        $this->browser()
            ->use(Authentication::assertNotAuthenticated())
            ->post('/login', ['body' => ['email' => 'mary@example.com', 'password' => '1234']])
            ->assertOn('/login')
            ->assertSuccessful()
            ->assertSee('Invalid CSRF token.')
            ->use(Authentication::assertNotAuthenticated())
        ;
    }

    /**
     * @test
     */
    public function remember_me_enabled_by_default(): void
    {
        UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);

        $this->browser()
            ->visit('/login')
            ->fillField('Email', 'mary@example.com')
            ->fillField('Password', '1234')
            ->click('Sign in')
            ->assertOn('/')
            ->assertSuccessful()
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
            ->use(Authentication::expireSession())
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
        ;
    }

    /**
     * @test
     */
    public function can_disable_remember_me(): void
    {
        UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);

        $this->browser()
            ->visit('/login')
            ->fillField('Email', 'mary@example.com')
            ->fillField('Password', '1234')
            ->uncheckField('Remember me')
            ->click('Sign in')
            ->assertOn('/')
            ->assertSuccessful()
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
            ->use(Authentication::expireSession())
            ->use(Authentication::assertNotAuthenticated())
        ;
    }

    /**
     * @test
     */
    public function fully_authenticated_login_redirect(): void
    {
        UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);

        $this->browser()
            ->visit('/login')
            ->fillField('Email', 'mary@example.com')
            ->fillField('Password', '1234')
            ->click('Sign in')
            ->assertOn('/')
            ->use(Authentication::assertAuthenticated())
            ->visit('/login')
            ->assertOn('/')
            ->use(Authentication::assertAuthenticated())
        ;
    }

    /**
     * @test
     */
    public function fully_authenticated_login_target(): void
    {
        UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);

        $this->browser()
            ->visit('/login')
            ->fillField('Email', 'mary@example.com')
            ->fillField('Password', '1234')
            ->click('Sign in')
            ->assertOn('/')
            ->use(Authentication::assertAuthenticated())
            ->visit('/login?target=/some/page')
            ->assertOn('/some/page')
            ->use(Authentication::assertAuthenticated())
        ;
    }

    /**
     * @test
     */
    public function can_fully_authenticate_if_only_remembered(): void
    {
        UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);

        $this->browser()
            ->visit('/login')
            ->fillField('Email', 'mary@example.com')
            ->fillField('Password', '1234')
            ->click('Sign in')
            ->assertOn('/')
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
            ->use(Authentication::expireSession())
            ->visit('/login')
            ->assertOn('/login')
            ->fillField('Password', '1234')
            ->click('Sign in')
            ->assertOn('/')
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
        ;
    }

    /**
     * @test
     */
    public function legacy_password_hash_is_automatically_migrated_on_login(): void
    {
        $user = UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);

        // set the password to a legacy hash (argon2id, 1234)
        $user->setPassword('$argon2id$v=19$m=10,t=3,p=1$K9AFR15goJiUD6AdpK0a6Q$RsP6y+FRnYUBovBmhVZO7wN6Caj2eI8dMTnm3+5aTxk');
        $user->save();

        $this->assertSame(\PASSWORD_ARGON2ID, \password_get_info($user->getPassword())['algo']);

        $this->browser()
            ->use(Authentication::assertNotAuthenticated())
            ->visit('/login')
            ->fillField('Email', 'mary@example.com')
            ->fillField('Password', '1234')
            ->click('Sign in')
            ->assertOn('/')
            ->assertSuccessful()
            ->use(Authentication::assertAuthenticatedAs('mary@example.com'))
        ;

        $this->assertSame(\PASSWORD_DEFAULT, \password_get_info($user->getPassword())['algo']);
    }

    /**
     * @test
     */
    public function auto_redirected_to_authenticated_resource_after_login(): void
    {
        // complete this test when you have a page that requires authentication
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function auto_redirected_to_fully_authenticated_resource_after_fully_authenticated(): void
    {
        // complete this test when/if you have a page that requires the user be "fully authenticated"
        $this->markTestIncomplete();
    }
}
