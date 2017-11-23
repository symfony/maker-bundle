<?= "<?php\n" ?>

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class <?= $controller_class_name ?> extends Controller
{
    /**
     * @Route("<?= $route_path ?>", name="<?= $route_name ?>")
     */
    public function index()
    {
        return $this->render('your_own_template.html.twig');
    }
}
