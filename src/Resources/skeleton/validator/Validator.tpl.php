<?= "<?php" . PHP_EOL ?>

namespace <?= $namespace ?>;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class <?= $class_name ?> extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /* @var <?= str_replace('Validator', '', $class_name) ?> $constraint */
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
