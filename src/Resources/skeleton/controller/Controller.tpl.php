<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Bundle\FrameworkBundle\Controller\<?= $parent_class_name; ?>;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class <?= $class_name; ?>
 * @package <?= $namespace; ?>
 */
class <?= $class_name; ?> extends <?= $parent_class_name; ?><?= "\n" ?>
{
    /**
     * @Route("<?= $route_path ?>", name="<?= $route_name ?>")
     */
    public function index()
    {
<?php if ($twig_installed) { ?>
        return $this->render('<?= $template_name ?>', [
            'controller_name' => '<?= $class_name ?>',
        ]);
<?php } else { ?>
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => '<?= $relative_path; ?>',
        ]);
<?php } ?>
    }
}
