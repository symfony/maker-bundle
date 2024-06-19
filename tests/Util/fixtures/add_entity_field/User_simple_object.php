<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\Column(type: 'object')]
    private ?object $someObject = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSomeObject(): ?object
    {
        return $this->someObject;
    }

    public function setSomeObject(object $someObject): static
    {
        $this->someObject = $someObject;

        return $this;
    }
}
