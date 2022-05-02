<?php

namespace App\Tests\Functional;

use App\Factory\UserFactory;
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
            ->assertAuthenticated('madison@example.com')
            ->visit('/logout')
            ->assertNotAuthenticated()
            ->visit('/login')
            ->fillField('Email', 'madison@example.com')
            ->fillField('Password', 'password')
            ->click('Sign in')
            ->assertOn('/')
            ->assertAuthenticated('madison@example.com')
        ;

        UserFactory::assert()->count(1);
        UserFactory::assert()->exists(['name' => 'Madison', 'email' => 'madison@example.com']);
    }

    public function testValidation(): void
    {
        $this->browser()
            ->throwExceptions()
            ->visit('/register')
            ->assertSuccessful()
            ->click('Register')
            ->assertOn('/register')
            ->assertSee('Email is required')
            ->assertSee('Please enter a password')
            ->assertSee('Name is required')
            ->assertNotAuthenticated()
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
            ->assertNotAuthenticated()
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
            ->assertNotAuthenticated()
        ;
    }
}