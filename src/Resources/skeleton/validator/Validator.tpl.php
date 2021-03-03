<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class <?= $class_name ?> extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /** @var <?= $constraint_class_name ?> $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        // TODO: implement the validation here
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
