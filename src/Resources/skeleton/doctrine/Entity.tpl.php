<?php echo "<?php\n" ?>

namespace <?php echo $namespace ?>;

<?php if ($api_resource) { ?>use ApiPlatform\Core\Annotation\ApiResource;
<?php } ?>
use Doctrine\ORM\Mapping as ORM;

/**
<?php if ($api_resource) { ?> * @ApiResource()
<?php } ?>
 * @ORM\Entity(repositoryClass="<?php echo $repository_full_class_name ?>")
<?php if ($should_escape_table_name) { ?> * @ORM\Table(name="`<?php echo $table_name ?>`")
<?php } ?>
 */
class <?php echo $class_name."\n" ?>
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
