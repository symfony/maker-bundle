<?php

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class ResetPasswordInput
{
    /**
     * @Groups({"reset-password:write"})
     * @Assert\NotBlank
     * @Assert\Email()
     */
    public ?string $email = null;
}
