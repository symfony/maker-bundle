<?php echo "<?php\n" ?>

namespace <?php echo $namespace ?>;

use Symfony\Bundle\FrameworkBundle\Controller\<?php echo $parent_class_name; ?>;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class <?php echo $class_name; ?> extends <?php echo $parent_class_name; ?><?php echo "\n" ?>
{
}
