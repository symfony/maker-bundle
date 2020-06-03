<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?php if ($api_resource): ?>use ApiPlatform\Core\Annotation\ApiResource;
<?php endif ?>
use <?= $repository_full_class_name ?>;
use Doctrine\ORM\Mapping as ORM;

/**
<?php if ($api_resource): ?>
<?php if (empty($api_resource_options)): ?> * @ApiResource()
<?php else: ?> * @ApiResource(
<?php foreach ($api_resource_options as $option) { ?>
 *     <?= $option ?>

<?php } ?>
 * )
<?php endif ?>
<?php endif ?>
 * @ORM\Entity(repositoryClass=<?= $repository_class_name ?>::class)
<?php if ($should_escape_table_name): ?> * @ORM\Table(name="`<?= $table_name ?>`")
<?php endif ?>
 */
class <?= $class_name."\n" ?>
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }
}
