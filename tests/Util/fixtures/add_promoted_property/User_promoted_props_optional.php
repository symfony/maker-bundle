<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
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

        #[ORM\Column(type: Types::TEXT, nullable: true)]
        private ?string $biography = null,

        #[ORM\Column(length: 255, nullable: true)]
        private ?string $lastName = null,
    ) {
        // the body of the constructor is kept as it is

        $this->firstName = ucfirst($firstName);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }
}
