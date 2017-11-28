//PHP_OPEN

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class <?php echo $controller_class_name; ?> extends AbstractController
{
    /**
     * @Route("<?php echo $route_path; ?>", name="<?php echo $route_name; ?>")
     */
    public function index()
    {
        return new Response('Welcome to your new controller!');
    }
}
