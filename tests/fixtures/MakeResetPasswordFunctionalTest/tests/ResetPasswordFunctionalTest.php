<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResetPasswordFunctionalTest extends WebTestCase
{
    public function testResetRequestRoute()
    {
        $client = static::createClient();
        $client->request('GET', '/reset-password');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testResetRequestRouteDeniesInvalidToken()
    {
        $client = static::createClient();
        $client->request('GET', '/reset-password/reset/badToken1234');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
    }

    public function testCheckEmailRouteRedirectsToRequestRouteIfUserNotAllowedToCheckEmail()
    {
        $client = static::createClient();
        $client->request('GET', '/reset-password/check-email');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $this->assertResponseRedirects('/reset-password');
    }
}
