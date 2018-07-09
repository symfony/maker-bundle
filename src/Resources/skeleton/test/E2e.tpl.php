<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Panthere\PanthereTestCase;

class <?= $class_name ?> extends PanthereTestCase
{
    public function testSomething()
    {
        $client = static::createPanthereClient();
        $crawler = $client->request('GET', '/');

        $this->assertCount(1, $crawler->filterXPath('//h1:[contains(text(), "Hello World")]'));
    }
}
