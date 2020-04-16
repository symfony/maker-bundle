<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

use Doctrine\Bundle\FixturesBundle\Fixture;
use <?php echo $object_manager_class; ?>;

class <?php echo $class_name ?> extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }
}
