<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use <?= $api_test_case_fqcn; ?>;

class <?= $class_name ?> extends ApiTestCase
{
    public function testSomething(): void
    {
        $response = static::createClient()->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['@id' => '/']);
    }
}
