<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

use PHPUnit\Framework\TestCase;

class <?php echo $class_name ?> extends TestCase
{
    public function testSomething()
    {
        $this->assertTrue(true);
    }
}
