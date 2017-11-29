<?= "<?php\n" ?>

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class <?= $controller_class_name ?> extends AbstractController
{
    /**
     * @Route("<?= $route_path ?>", name="<?= $route_name ?>")
     */
    public function index()
    {
<?php if ($twig_installed) { ?>
        return $this->render('<?= $twig_file ?>', [
            'controller_name' => '<?= $controller_name ?>',
        ]);
<?php } else { ?>
        return new Response('Welcome to your new controller!');
<?php } ?>
    }
}
