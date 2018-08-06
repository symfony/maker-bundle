<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Panthere\PanthereTestCase;

class <?= $class_name ?> extends PanthereTestCase
{
    public function testSomething()
    {
        $client = static::createPanthereClient();
        $crawler = $client->request('GET', '/');

        $this->assertContains('Hello World', $crawler->filter('h1')->text());
    }
}
