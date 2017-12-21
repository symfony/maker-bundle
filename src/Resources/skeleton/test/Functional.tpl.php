<?= "<?php\n" ?>

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class <?= $test_class_name ?> extends WebTestCase
{
    public function testSomething()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame(1, $crawler->filter('h1:contains("Hello World")')->count());
    }
}
