<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
* Class <?= $class_name ?>
* @package <?= $namespace; ?>
*/
class <?= $class_name ?> extends ConstraintValidator
{
    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint <?= $constraint_class_name ?> */

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
