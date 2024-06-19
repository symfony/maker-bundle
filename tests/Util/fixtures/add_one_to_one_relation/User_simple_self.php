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

    #[ORM\OneToOne(targetEntity: self::class, inversedBy: 'user', cascade: ['persist', 'remove'])]
    private ?self $embeddedUser = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmbeddedUser(): ?self
    {
        return $this->embeddedUser;
    }

    public function setEmbeddedUser(?self $embeddedUser): static
    {
        $this->embeddedUser = $embeddedUser;

        return $this;
    }
}
