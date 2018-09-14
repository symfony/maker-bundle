<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Bundle\FrameworkBundle\Controller\<?= $parent_class_name; ?>;
<?php if ($use_annotations): ?>
use Symfony\Component\Routing\Annotation\Route;
<?php endif ?>

class <?= $class_name; ?> extends <?= $parent_class_name; ?><?= "\n" ?>
{
<?php if ($use_annotations): ?>
    /**
     * @Route("<?= $route_path ?>", name="<?= $route_name ?>")
     */
<?php endif ?>
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
