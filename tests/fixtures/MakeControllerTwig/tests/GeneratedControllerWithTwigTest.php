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

class GeneratedControllerWithTwigTest extends WebTestCase
{
    public function testController()
    {
        $client = self::createClient();
        $client->request('GET', '/foo/twig');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertStringContainsString('Hello FooTwigController', $client->getResponse()->getContent());
    }
}
