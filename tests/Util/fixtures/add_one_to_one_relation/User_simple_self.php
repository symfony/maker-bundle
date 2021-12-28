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

    #[ORM\OneToOne(inversedBy: 'user', targetEntity: self::class, cascade: ['persist', 'remove'])]
    private $embeddedUser;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmbeddedUser(): ?self
    {
        return $this->embeddedUser;
    }

    public function setEmbeddedUser(?self $embeddedUser): self
    {
        $this->embeddedUser = $embeddedUser;

        return $this;
    }
}
