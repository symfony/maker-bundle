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
    use Factories;
    use HasBrowser;
    use ResetDatabase;

    public function testCanLoginAndLogout(): void
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

    public function testLoginWithTarget(): void
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

    public function testLoginWithInvalidPassword(): void
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

    public function testLoginWithInvalidEmail(): void
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

    public function testLoginWithInvalidCsrf(): void
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

    public function testRememberMeEnabledByDefault(): void
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

    public function testCanDisableRememberMe(): void
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

    public function testFullyAuthenticatedLoginRedirect(): void
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

    public function testFullyAuthenticatedLoginTarget(): void
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

    public function testCanFullyAuthenticateIfOnlyRemembered(): void
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

    public function testLegacyPasswordHashIsAutomaticallyMigratedOnLogin(): void
    {
        $user = UserFactory::createOne(['email' => 'mary@example.com', 'password' => '1234']);

        // set the password to a legacy hash (argon2id, 1234)
        $user->setPassword('$argon2id$v=19$m=10,t=3,p=1$K9AFR15goJiUD6AdpK0a6Q$RsP6y+FRnYUBovBmhVZO7wN6Caj2eI8dMTnm3+5aTxk');
        $user->save();

        $this->assertSame(\PASSWORD_ARGON2ID, password_get_info($user->getPassword())['algo']);

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

        $this->assertSame(\PASSWORD_DEFAULT, password_get_info($user->getPassword())['algo']);
    }

    public function testAutoRedirectedToAuthenticatedResourceAfterLogin(): void
    {
        // complete this test when you have a page that requires authentication
        $this->markTestIncomplete();
    }

    public function testAutoRedirectedToFullyAuthenticatedResourceAfterFullyAuthenticated(): void
    {
        // complete this test when/if you have a page that requires the user be "fully authenticated"
        $this->markTestIncomplete();
    }
}
