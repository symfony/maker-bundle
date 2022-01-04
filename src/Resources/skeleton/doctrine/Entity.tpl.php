<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?php if ($api_resource && class_exists(\ApiPlatform\Metadata\ApiResource::class)): ?>
use ApiPlatform\Metadata\ApiResource;
<?php elseif ($api_resource): ?>
use ApiPlatform\Core\Annotation\ApiResource;
<?php endif ?>
use <?= $repository_full_class_name ?>;
use Doctrine\ORM\Mapping as ORM;
<?php if ($broadcast): ?>
use Symfony\UX\Turbo\Attribute\Broadcast;
<?php endif ?>
<?php if ($id_uuid && class_exists(\Symfony\Component\Uid\Uuid::class)): ?>
use Symfony\Component\Uid\Uuid;
<?php elseif ($id_ulid && class_exists(\Symfony\Component\Uid\Ulid::class)): ?>
use Symfony\Component\Uid\Ulid;
<?php endif ?>

<?php if (!$use_attributes || !$doctrine_use_attributes): ?>
/**
<?php if ($api_resource && !$use_attributes): ?>
* @ApiResource()
<?php endif ?>
<?php if ($broadcast && !$use_attributes): ?>
* @Broadcast()
<?php endif ?>
* @ORM\Entity(repositoryClass=<?= $repository_class_name ?>::class)
<?php if ($should_escape_table_name): ?>
* @ORM\Table(name="`<?= $table_name ?>`")
<?php endif ?>
*/
<?php endif ?>
<?php if ($doctrine_use_attributes): ?>
#[ORM\Entity(repositoryClass: <?= $repository_class_name ?>::class)]
<?php if ($should_escape_table_name): ?>
#[ORM\Table(name: '`<?= $table_name ?>`')]
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

<?php if (!$doctrine_use_attributes): ?>
    /**
    * @ORM\Id
    * @ORM\GeneratedValue
    * @ORM\Column(type="integer")
    */
<?php else: ?>
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
<?php endif ?>
    private $id;

<?php if (!$doctrine_use_attributes && ($id_uuid && class_exists(\Symfony\Component\Uid\Uuid::class))): ?>
    /**
    * @ORM\Column(type="uuid", unique=true)
    */
    private ?Uuid $uuid;
<?php elseif (!$doctrine_use_attributes && ($id_uuid && class_exists(\Symfony\Component\Uid\Uuid::class))): ?>
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $uuid;
<?php endif ?>

<?php if (!$doctrine_use_attributes && ($id_ulid && class_exists(\Symfony\Component\Uid\Ulid::class))): ?>
    /**
    * @ORM\Column(type="ulid", unique=true)
    */
    private ?Ulid $ulid;
<?php elseif (!$doctrine_use_attributes && ($id_ulid && class_exists(\Symfony\Component\Uid\Ulid::class))): ?>
    #[ORM\Column(type: 'ulid', unique: true)]
    private ?Ulid $ulid;
<?php endif ?>

    public function getId(): ?int
    {
        return $this->id;
    }

<?php if ($id_uuid && class_exists(\Symfony\Component\Uid\Uuid::class)): ?>
    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }
<?php elseif ($id_ulid && class_exists(\Symfony\Component\Uid\Ulid::class)): ?>
    public function getUlid(): ?Ulid
    {
        return $this->ulid;
    }
<?php endif ?>

}