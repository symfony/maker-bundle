<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

use Symfony\Bundle\FrameworkBundle\Controller\<?php echo $parent_class_name; ?>;
use Symfony\Component\Routing\Annotation\Route;

class <?php echo $class_name; ?> extends <?php echo $parent_class_name; ?><?php echo "\n" ?>
{
    /**
     * @Route("<?php echo $route_path ?>", name="<?php echo $route_name ?>")
     */
    public function index()
    {
<?php if ($with_template) { ?>
        return $this->render('<?php echo $template_name ?>', [
            'controller_name' => '<?php echo $class_name ?>',
        ]);
<?php } else { ?>
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => '<?php echo $relative_path; ?>',
        ]);
<?php } ?>
    }
}
