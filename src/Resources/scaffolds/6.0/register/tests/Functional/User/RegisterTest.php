<?php

namespace App\Tests\Functional\User;

use App\Factory\UserFactory;
use App\Tests\Browser\Authentication;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class RegisterTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use ResetDatabase;

    public function testCanRegister(): void
    {
        UserFactory::assert()->empty();

        $this->browser()
            ->visit('/register')
            ->assertSuccessful()
            ->fillField('Name', 'Madison')
            ->fillField('Email', 'madison@example.com')
            ->fillField('Password', 'password')
            ->click('Register')
            ->assertOn('/')
            ->assertSeeIn('.alert', 'You\'ve successfully registered and are now logged in.')
            ->use(Authentication::assertAuthenticatedAs('madison@example.com'))
            ->visit('/logout')
            ->use(Authentication::assertNotAuthenticated())
            ->visit('/login')
            ->fillField('Email', 'madison@example.com')
            ->fillField('Password', 'password')
            ->click('Sign in')
            ->assertOn('/')
            ->use(Authentication::assertAuthenticatedAs('madison@example.com'))
        ;

        UserFactory::assert()->count(1);
        UserFactory::assert()->exists(['name' => 'Madison', 'email' => 'madison@example.com']);
    }

    public function testNameIsRequired(): void
    {
        $this->browser()
            ->throwExceptions()
            ->visit('/register')
            ->assertSuccessful()
            ->fillField('Email', 'madison@example.com')
            ->fillField('Password', 'password')
            ->click('Register')
            ->assertOn('/register')
            ->assertSee('Name is required')
            ->use(Authentication::assertNotAuthenticated())
        ;
    }

    public function testEmailIsRequired(): void
    {
        $this->browser()
            ->throwExceptions()
            ->visit('/register')
            ->assertSuccessful()
            ->fillField('Name', 'Madison')
            ->fillField('Password', 'password')
            ->click('Register')
            ->assertOn('/register')
            ->assertSee('Email is required')
            ->use(Authentication::assertNotAuthenticated())
        ;
    }

    public function testEmailMustBeEmailAddress(): void
    {
        $this->browser()
            ->throwExceptions()
            ->visit('/register')
            ->assertSuccessful()
            ->fillField('Name', 'Madison')
            ->fillField('Email', 'invalid-email')
            ->fillField('Password', 'password')
            ->click('Register')
            ->assertOn('/register')
            ->assertSee('This is not a valid email address')
            ->use(Authentication::assertNotAuthenticated())
        ;
    }

    public function testEmailMustBeUnique(): void
    {
        UserFactory::createOne(['email' => 'madison@example.com']);

        $this->browser()
            ->throwExceptions()
            ->visit('/register')
            ->assertSuccessful()
            ->fillField('Name', 'Madison')
            ->fillField('Email', 'madison@example.com')
            ->fillField('Password', 'password')
            ->click('Register')
            ->assertOn('/register')
            ->assertSee('There is already an account with this email')
            ->use(Authentication::assertNotAuthenticated())
        ;
    }

    public function testPasswordIsRequired(): void
    {
        $this->browser()
            ->throwExceptions()
            ->visit('/register')
            ->assertSuccessful()
            ->fillField('Name', 'Madison')
            ->fillField('Email', 'madison@example.com')
            ->click('Register')
            ->assertOn('/register')
            ->assertSee('Please enter a password')
            ->use(Authentication::assertNotAuthenticated())
        ;
    }

    public function testPasswordMustBeMinLength(): void
    {
        $this->browser()
            ->throwExceptions()
            ->visit('/register')
            ->assertSuccessful()
            ->fillField('Name', 'Madison')
            ->fillField('Email', 'madison@example.com')
            ->fillField('Password', '1234')
            ->click('Register')
            ->assertOn('/register')
            ->assertSee('Your password should be at least 6 characters')
            ->use(Authentication::assertNotAuthenticated())
        ;
    }
}
