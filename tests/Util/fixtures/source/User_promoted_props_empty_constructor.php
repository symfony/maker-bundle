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

    public function __construct(
        #[ORM\Column(length: 255)]
        private string $firstName,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
