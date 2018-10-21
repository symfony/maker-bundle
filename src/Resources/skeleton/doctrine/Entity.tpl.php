<?= "<?php" . PHP_EOL ?>

namespace <?= $namespace ?>;

<?= $api_resource ? "use ApiPlatform\Core\Annotation\ApiResource;" . PHP_EOL : null ?>
use Doctrine\ORM\Mapping as ORM;

/**
 * Class <?= $class_name . PHP_EOL ?>
<?= $api_resource ? " * @ApiResource()" . PHP_EOL : null ?>
 * @ORM\Entity(repositoryClass="<?= $repository_full_class_name ?>")
 */
class <?= $class_name . PHP_EOL ?>
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }
}
