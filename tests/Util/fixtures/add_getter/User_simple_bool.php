<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isFooProp(): ?bool
    {
        return $this->fooProp;
    }
}
