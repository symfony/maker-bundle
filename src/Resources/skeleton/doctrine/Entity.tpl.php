<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>

<?php if (!$use_attributes || !$doctrine_use_attributes): ?>
/**
<?php if ($api_resource && !$use_attributes): ?> * @ApiResource()
<?php endif ?>
<?php if ($broadcast && !$use_attributes): ?> * @Broadcast()
<?php endif ?>
 * @ORM\Entity(repositoryClass=<?= $repository_class_name ?>::class)
<?php if ($should_escape_table_name): ?> * @ORM\Table(name="`<?= $table_name ?>`")
<?php endif ?>
 */
<?php endif ?>
<?php if ($doctrine_use_attributes): ?>
#[ORM\Entity(repositoryClass: <?= $repository_class_name ?>::class)]
<?php if ($should_escape_table_name): ?>#[ORM\Table(name: '`<?= $table_name ?>`')]
<?php endif ?>
<?php endif?>
<?php if ($api_resource && $use_attributes): ?>
#[ApiResource]
<?php endif ?>
<?php if ($broadcast && $use_attributes): ?>
#[Broadcast]
<?php endif ?>
class <?= $class_name."\n" ?>
{
    <?php if (!$doctrine_use_attributes): ?>/**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    <?php else: ?>#[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    <?php endif ?>private $id;

    public function getId(): ?int
    {
        return $this->id;
    }
}
