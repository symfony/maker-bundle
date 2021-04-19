<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Bundle\FrameworkBundle\Controller\<?= $parent_class_name; ?>;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class <?= $class_name; ?> extends <?= $parent_class_name; ?><?= "\n" ?>
{
<?php if ($use_attributes) { ?>
    #[Route('<?= $route_path ?>', name: '<?= $route_name ?>')]
<?php } else { ?>
    /**
     * @Route("<?= $route_path ?>", name="<?= $route_name ?>")
     */
<?php } ?>
    public function index(): Response
    {
<?php if ($with_template) { ?>
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
