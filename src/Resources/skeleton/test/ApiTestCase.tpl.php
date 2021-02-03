<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;

class <?= $class_name ?> extends ApiTestCase
{
    public function testSomething(): void
    {
        $response = static::createClient()->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['@id' => '/']);
    }
}
