<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Component\Validator\Constraint;

/**
 * Class <?= $class_name ?>
 * @Annotation
 * @package <?= $namespace; ?>
 */
class <?= $class_name ?> extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'The value "{{ value }}" is not valid.';
}
