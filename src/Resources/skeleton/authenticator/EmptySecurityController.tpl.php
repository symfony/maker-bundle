<?= "<?php\n" ?>

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\<?= $parent_class_name; ?>;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class <?= $class_name; ?>
 * @package App
 */
class <?= $class_name; ?> extends <?= $parent_class_name; ?><?= "\n" ?>
{
}
