<?= "<?php\n" ?>

namespace App\Entity<?= $entity_namespace ? '\\'.$entity_namespace : '' ?>;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\<?= $entity_namespace ? $entity_namespace.'\\' : '' ?><?= $repository_class_name ?>")
 */
class <?= $entity_class_name."\n" ?>
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    // add your own fields
}
