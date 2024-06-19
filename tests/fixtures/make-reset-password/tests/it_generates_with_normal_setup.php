<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResetPasswordFunctionalTest extends WebTestCase
{
    public function testResetRequestRoute(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reset-password');

        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testResetRequestRouteDeniesInvalidToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reset-password/reset/badToken1234');

        self::assertSame(302, $client->getResponse()->getStatusCode());
    }

    public function testCheckEmailPageIsAlwaysAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reset-password/check-email');

        self::assertResponseIsSuccessful();
        self::assertPageTitleSame('Password Reset Email Sent');
    }
}
