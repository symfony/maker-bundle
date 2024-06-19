<?php /* @deprecated remove this method when removing make:unit-test and make:functional-test */ ?>
<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

class <?= $class_name ?> extends TestCase
{
    public function testSomething(): void
    {
        $this->assertTrue(true);
    }
}
