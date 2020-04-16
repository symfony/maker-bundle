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

class GeneratedCrudControllerTest extends WebTestCase
{
    public function testIndexAction()
    {
        $client = self::createClient();
        $client->request('GET', '/sweet/food/');
        $this->assertTrue($client->getResponse()->isSuccessful());
    }
}
