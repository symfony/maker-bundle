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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setIsFooProp(bool $isFooProp): static
    {
        $this->isFooProp = $isFooProp;

        return $this;
    }
}
