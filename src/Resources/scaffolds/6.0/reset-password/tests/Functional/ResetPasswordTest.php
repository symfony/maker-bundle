<?php

namespace App\Tests\Functional;

use App\Factory\ResetPasswordRequestFactory;
use App\Factory\UserFactory;
use App\Tests\Browser\Authentication;
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
    use HasBrowser, Factories, ResetDatabase, InteractsWithMailer;

    /**
     * @test
     */
    public function can_reset_password(): void
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
            ->assertSeeIn('.flash', 'Your password was successfully reset, you are now logged in.')
            ->use(Authentication::assertAuthenticatedAs('john@example.com'))
            ->visit('/logout')
            ->visit('/login')
            ->fillField('Email', 'john@example.com')
            ->fillField('Password', 'new-password')
            ->click('Sign in')
            ->assertOn('/')
            ->use(Authentication::assertAuthenticatedAs('john@example.com'))
        ;
    }

    /**
     * @test
     */
    public function request_email_is_required(): void
    {
        $this->browser()
            ->visit('/reset-password')
            ->click('Send password reset email')
            ->assertOn('/reset-password')
            ->assertSee('Please enter your email')
        ;

        $this->mailer()->assertNoEmailSent();
    }

    /**
     * @test
     */
    public function request_email_must_be_an_email(): void
    {
        $this->browser()
            ->visit('/reset-password')
            ->fillField('Email', 'invalid')
            ->click('Send password reset email')
            ->assertOn('/reset-password')
            ->assertSee('This is not a valid email address')
        ;

        $this->mailer()->assertNoEmailSent();
    }

    /**
     * @test
     */
    public function requests_are_throttled(): void
    {
        UserFactory::createOne(['email' => 'john@example.com']);

        $this->browser()
            ->visit('/reset-password')
            ->fillField('Email', 'john@example.com')
            ->click('Send password reset email')
            ->assertOn('/reset-password/check-email')
            ->assertSuccessful()
            ->assertSee('Password Reset Email Sent')
            ->visit('/reset-password')
            ->fillField('Email', 'john@example.com')
            ->click('Send password reset email')
            ->assertOn('/')
            ->assertSeeIn('.flash', 'You have already requested a reset password email. Please check your email or try again soon.')
        ;

        $this->mailer()->assertSentEmailCount(1);

        ResetPasswordRequestFactory::assert()->count(1);
    }

    /**
     * @test
     */
    public function can_request_again_after_throttle_expires(): void
    {
        UserFactory::createOne(['email' => 'john@example.com']);

        $this->browser()
            ->visit('/reset-password')
            ->fillField('Email', 'john@example.com')
            ->click('Send password reset email')
            ->assertOn('/reset-password/check-email')
            ->assertSuccessful()
            ->assertSee('Password Reset Email Sent')
            ->use(function() {
                ResetPasswordRequestFactory::first()
                    ->forceSet('requestedAt', new \DateTimeImmutable('-16 minutes'))
                    ->save()
                ;
            })
            ->visit('/reset-password')
            ->fillField('Email', 'john@example.com')
            ->click('Send password reset email')
            ->assertOn('/reset-password/check-email')
            ->assertSuccessful()
            ->assertSee('Password Reset Email Sent')
        ;

        $this->mailer()->assertSentEmailCount(2);

        ResetPasswordRequestFactory::assert()->count(2);
    }

    /**
     * @test
     */
    public function request_does_not_expose_if_user_was_not_found(): void
    {
        $this->browser()
            ->visit('/reset-password')
            ->fillField('Email', 'john@example.com')
            ->click('Send password reset email')
            ->assertOn('/reset-password/check-email')
            ->assertSuccessful()
            ->assertSee('Password Reset Email Sent')
        ;

        $this->mailer()->assertNoEmailSent();
    }

    /**
     * @test
     */
    public function reset_password_is_required(): void
    {
        $user = UserFactory::createOne(['email' => 'john@example.com']);
        $currentPassword = $user->getPassword();

        $this->browser()
            ->visit('/reset-password')
            ->assertSuccessful()
            ->fillField('Email', 'john@example.com')
            ->click('Send password reset email')
            ->visit($this->mailer()->sentEmails()->first()->getHeaders()->get('X-CTA')->getBody())
            ->click('Reset password')
            ->assertOn('/reset-password/reset')
            ->assertSee('Please enter a password')
        ;

        $this->assertSame($currentPassword, UserFactory::find(['email' => 'john@example.com'])->getPassword());
    }

    /**
     * @test
     */
    public function reset_passwords_must_match(): void
    {
        $user = UserFactory::createOne(['email' => 'john@example.com']);
        $currentPassword = $user->getPassword();

        $this->browser()
            ->visit('/reset-password')
            ->assertSuccessful()
            ->fillField('Email', 'john@example.com')
            ->click('Send password reset email')
            ->visit($this->mailer()->sentEmails()->first()->getHeaders()->get('X-CTA')->getBody())
            ->fillField('New password', 'new-password')
            ->fillField('Repeat Password', 'mismatch-password')
            ->click('Reset password')
            ->assertOn('/reset-password/reset')
            ->assertSee('The password fields must match.')
        ;

        $this->assertSame($currentPassword, UserFactory::find(['email' => 'john@example.com'])->getPassword());
    }

    /**
     * @test
     */
    public function reset_password_must_be_min_length(): void
    {
        $user = UserFactory::createOne(['email' => 'john@example.com']);
        $currentPassword = $user->getPassword();

        $this->browser()
            ->visit('/reset-password')
            ->assertSuccessful()
            ->fillField('Email', 'john@example.com')
            ->click('Send password reset email')
            ->visit($this->mailer()->sentEmails()->first()->getHeaders()->get('X-CTA')->getBody())
            ->fillField('New password', '1234')
            ->fillField('Repeat Password', '1234')
            ->click('Reset password')
            ->assertOn('/reset-password/reset')
            ->assertSee('Your password should be at least 6 characters')
        ;

        $this->assertSame($currentPassword, UserFactory::find(['email' => 'john@example.com'])->getPassword());
    }

    /**
     * @test
     */
    public function cannot_reset_with_invalid_token(): void
    {
        $this->browser()
            ->visit('/reset-password/reset/invalid-token')
            ->assertOn('/')
            ->assertSeeIn('.flash', 'The reset password link is invalid. Please try to reset your password again.')
        ;
    }

    /**
     * @test
     */
    public function can_use_old_token_even_after_requesting_another(): void
    {
        UserFactory::createOne(['email' => 'john@example.com']);

        $this->browser()
            ->visit('/reset-password')
            ->fillField('Email', 'john@example.com')
            ->click('Send password reset email')
            ->assertOn('/reset-password/check-email')
            ->assertSuccessful()
            ->assertSee('Password Reset Email Sent')
            ->use(function() {
                ResetPasswordRequestFactory::first()
                    ->forceSet('requestedAt', new \DateTimeImmutable('-16 minutes'))
                    ->save()
                ;
            })
            ->visit('/reset-password')
            ->fillField('Email', 'john@example.com')
            ->click('Send password reset email')
            ->assertOn('/reset-password/check-email')
            ->assertSuccessful()
            ->assertSee('Password Reset Email Sent')
            ->use(function() {
                ResetPasswordRequestFactory::assert()->count(2);
            })
            ->visit($this->mailer()->sentEmails()->first()->getHeaders()->get('X-CTA')->getBody())
            ->assertOn('/reset-password/reset')
            ->fillField('New password', 'new-password')
            ->fillField('Repeat Password', 'new-password')
            ->click('Reset password')
            ->assertOn('/')
        ;

        ResetPasswordRequestFactory::assert()->empty();
    }

    /**
     * @test
     */
    public function reset_tokens_expire(): void
    {
        UserFactory::createOne(['email' => 'john@example.com']);

        $this->browser()
            ->visit('/reset-password')
            ->fillField('Email', 'john@example.com')
            ->click('Send password reset email')
            ->assertOn('/reset-password/check-email')
            ->assertSuccessful()
            ->assertSee('Password Reset Email Sent')
            ->use(function() {
                ResetPasswordRequestFactory::first()
                    ->forceSet('expiresAt', new \DateTimeImmutable('-10 minutes'))
                    ->save()
                ;
            })
            ->visit($this->mailer()->sentEmails()->first()->getHeaders()->get('X-CTA')->getBody())
            ->assertOn('/')
            ->assertSuccessful()
            ->assertSeeIn('.flash', 'The link in your email is expired. Please try to reset your password again.')
        ;
    }

    /**
     * @test
     */
    public function cannot_use_token_after_password_change(): void
    {
        UserFactory::createOne(['email' => 'john@example.com']);

        $this->browser()
            ->visit('/reset-password')
            ->assertSuccessful()
            ->fillField('Email', 'john@example.com')
            ->click('Send password reset email')
            ->assertOn('/reset-password/check-email')
            ->visit($resetUrl = $this->mailer()->sentEmails()->first()->getHeaders()->get('X-CTA')->getBody())
            ->fillField('New password', 'new-password')
            ->fillField('Repeat Password', 'new-password')
            ->click('Reset password')
            ->assertOn('/')
            ->assertSeeIn('.flash', 'Your password was successfully reset, you are now logged in.')
            ->visit('/logout')
            ->visit($resetUrl)
            ->assertOn('/')
            ->assertSuccessful()
            ->assertSeeIn('.flash', 'The reset password link is invalid. Please try to reset your password again.')
        ;
    }

    /**
     * @test
     */
    public function old_tokens_are_garbage_collected(): void
    {
        $user = UserFactory::createOne(['email' => 'jane@example.com']);

        ResetPasswordRequestFactory::createOne([
                'user' => $user,
                'selector' => 'selector',
                'hashedToken' => 'hash',
                'expiresAt' => new \DateTimeImmutable('-1 month'),
            ])
            ->forceSet('requestedAt', new \DateTimeImmutable('-1 month'))
            ->save()
        ;

        ResetPasswordRequestFactory::assert()->exists(['selector' => 'selector']);

        $this->browser()
            ->visit('/reset-password')
            ->assertSuccessful()
            ->fillField('Email', 'jane@example.com')
            ->click('Send password reset email')
            ->assertOn('/reset-password/check-email')
        ;

        ResetPasswordRequestFactory::assert()
            ->count(1)
            ->notExists(['selector' => 'selector'])
        ;
    }
}
