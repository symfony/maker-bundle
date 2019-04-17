<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use Symfony\Bundle\FrameworkBundle\Controller\<?= $parent_class_name ?>;
use Symfony\Component\Routing\Annotation\Route;

class <?= $class_name ?> extends <?= $parent_class_name ?><?= "\n" ?>
{
    /**
     * @Route("<?= $route_path ?>", name="<?= $route_name ?>")
     */
    public function index()
    {
        return $this->json(['message' => 'Welcome to your new controller!']);
    }
}
