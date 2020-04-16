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

class GeneratedEntityTest extends WebTestCase
{
    public function testGeneratedEntity()
    {
        // login then access a protected page
        $client = self::createClient();
        $client->request('GET', '/login?username=hal9000');

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Homepage Success. Hello: hal9000', $client->getResponse()->getContent());
    }
}
