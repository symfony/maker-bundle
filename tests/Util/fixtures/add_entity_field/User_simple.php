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

    #[ORM\Column(length: 255, nullable: false, options: ['comment' => 'new field'])]
    private ?string $fooProp = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFooProp(): ?string
    {
        return $this->fooProp;
    }

    public function setFooProp(string $fooProp): static
    {
        $this->fooProp = $fooProp;

        return $this;
    }
}
