<?= "<?php\n" ?>

namespace <?= $test_namespace ?>;

use PHPUnit\Framework\TestCase;

class <?= $test_class_name ?> extends TestCase
{
    public function testSomething()
    {
        $this->assertTrue(true);
    }
}
