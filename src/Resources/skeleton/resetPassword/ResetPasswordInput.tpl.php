<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class <?= $class_name."\n" ?>
{
    /**
     * @Groups({"reset-password:write"})
     * @Assert\NotBlank
     * @Assert\Email()
     */
    public <?= $use_typed_properties ? '?string ' : null ?>$email = null;
}
