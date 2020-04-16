<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class <?php echo $class_name ?> extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'The value "{{ value }}" is not valid.';
}
