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

    #[ORM\Column(type: Types::OBJECT)]
    private $someObject;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSomeObject(): ?object
    {
        return $this->someObject;
    }

    public function setSomeObject(object $someObject): self
    {
        $this->someObject = $someObject;

        return $this;
    }
}
