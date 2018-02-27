<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="<?= $repository_full_class_name ?>")
 */
class <?= $class_name."\n" ?>
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    // add your own fields
}
