<?= "<?php" . PHP_EOL ?>

namespace <?= $namespace; ?>;

use Symfony\Bundle\FrameworkBundle\Controller\<?= $parent_class_name; ?>;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\<?= $twig_installed ? "Response" : "JsonResponse" ?>;

class <?= $class_name; ?> extends <?= $parent_class_name; ?><?= PHP_EOL ?>
{
    /**
     * <?= ucwords($route_name) ?> index page
     *
     * @Route("<?= $route_path ?>", name="<?= $route_name ?>")
     * @return <?= $twig_installed ? "Response" : "JsonResponse" ?><?= PHP_EOL ?>
     */
    public function index(): <?= $twig_installed ? "Response" : "JsonResponse" ?><?= PHP_EOL ?>
    {
<?php if($twig_installed): ?>
        return $this->render('<?= $template_name ?>', [
            'controller_name' => '<?= $class_name ?>',
        ]);
<?php else: ?>
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => '<?= $relative_path; ?>',
        ]);
<?php endif ?>
    }
}
