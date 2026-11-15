<?php

namespace App\Entity;

class User
{
    public function __construct(
        #[ORM\Column(length: 255)]
        private string $lastName,
    ) {
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }
}
