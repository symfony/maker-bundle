<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use PHPUnit\Framework\TestCase;

/**
* Class <?= $class_name ?>
* @package <?= $namespace; ?>
*/
class <?= $class_name ?> extends TestCase
{
    public function testSomething()
    {
        $this->assertTrue(true);
    }
}
