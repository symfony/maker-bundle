//PHP_OPEN

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class <?php echo $validator_class_name; ?> extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /** @var $constraint <?php echo $constraint_class_name; ?> */

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
