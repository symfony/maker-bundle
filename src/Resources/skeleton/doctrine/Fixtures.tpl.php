<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

class <?= $class_name ?> extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }
}
