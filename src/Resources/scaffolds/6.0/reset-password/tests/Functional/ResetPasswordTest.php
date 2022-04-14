<?php

namespace App\Tests\Functional;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Mailer\Test\InteractsWithMailer;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ResetPasswordTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use InteractsWithMailer;
    use ResetDatabase;

    public function testCanResetPassword(): void
    {
        UserFactory::createOne(['email' => 'john@example.com', 'name' => 'John', 'password' => '1234']);

        $this->browser()
            ->visit('/reset-password')
            ->assertSuccessful()
            ->fillField('Email', 'john@example.com')
            ->click('Send password reset email')
            ->assertOn('/reset-password/check-email')
            ->assertSuccessful()
            ->assertSee('Password Reset Email Sent')
        ;

        $email = $this->mailer()
            ->sentEmails()
            ->assertCount(1)
            ->first()
        ;
        $resetUrl = $email->getHeaders()->get('X-CTA')?->getBody();

        $this->assertNotNull($resetUrl, 'The reset url header was not set.');

        $email
            ->assertTo('john@example.com', 'John')
            ->assertContains('To reset your password, please visit the following link')
            ->assertContains($resetUrl)
            ->assertHasTag('reset-password')
        ;

        $this->browser()
            ->visit($resetUrl)
            ->fillField('New password', 'new-password')
            ->fillField('Repeat Password', 'new-password')
            ->click('Reset password')
            ->assertOn('/')
            ->assertSeeIn('.alert', 'Your password was successfully reset, you are now logged in.')
            ->assertAuthenticated('john@example.com')
            ->visit('/logout')
            ->visit('/login')
            ->fillField('Email', 'john@example.com')
            ->fillField('Password', 'new-password')
            ->click('Sign in')
            ->assertOn('/')
            ->assertAuthenticated('john@example.com')
        ;
    }
}
