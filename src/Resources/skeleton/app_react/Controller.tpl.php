<?= "<?php\n" ?>

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class <?= $class_name; ?> extends AbstractController
{
    /**
     * @Route("<?= $route_path ?>", name="<?= $route_name ?>")
     */
    public function index()
    {
        return $this->render('<?= $template_name ?>/index.html.twig');
    }
}
