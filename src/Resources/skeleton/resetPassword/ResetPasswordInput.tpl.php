<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class <?= $class_name."\n" ?>
{
    /**
    * @Assert\NotBlank(groups={"postValidation"})
    * @Assert\Email(groups={"postValidation"})
    * @Groups({"reset-password:post"})
    */
    public <?= $use_typed_properties ? '?string ' : null ?>$email = null;

    /**
    * @Assert\NotBlank(groups={"putValidation"})
    * @Groups({"reset-password:put"})
    */
    public <?= $use_typed_properties ? '?string ' : null ?>$token = null;

    /**
    * @Assert\NotBlank(groups={"putValidation"})
    * @Groups({"reset-password:put"})
    */
    public <?= $use_typed_properties ? '?string ' : null ?>$plainTextPassword = null;
}
