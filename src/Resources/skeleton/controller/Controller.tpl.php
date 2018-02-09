<?= "<?php\n" ?>

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class <?= $controller_class_name ?> extends Controller
{
    /**
     * @Route("<?= $route_path ?>", name="<?= $route_name ?>")
     */
    public function index()
    {
<?php if ($twig_installed) { ?>
        return $this->render('<?= $twig_file ?>', [
            'controller_name' => '<?= $controller_class_name ?>',
        ]);
<?php } else { ?>
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => '<?= $relative_path; ?>',
        ]);
<?php } ?>
    }
}
