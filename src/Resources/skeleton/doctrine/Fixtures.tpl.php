<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;

class <?= $class_name ?> extends Fixture
{
    public function load(EntityManagerInterface $manager)
    {
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }
}
