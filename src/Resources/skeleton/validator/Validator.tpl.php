<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class <?php echo $class_name ?> extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint \<?php echo $constraint_class_name ?> */

        if (null === $value || '' === $value) {
            return;
        }

        // TODO: implement the validation here
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
