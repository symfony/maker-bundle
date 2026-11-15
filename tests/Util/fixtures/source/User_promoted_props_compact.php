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
        private string $firstName,
        private string $nickname // not mapped
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }
}
