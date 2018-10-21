<?= "<?php" . PHP_EOL ?>

namespace <?= $namespace; ?>;

use PHPUnit\Framework\TestCase;

class <?= $class_name ?> extends TestCase
{
    public function testSomething()
    {
        $this->assertTrue(true);
    }
}
