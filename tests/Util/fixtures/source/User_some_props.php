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

    #[ORM\Column]
    private ?string $firstName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Some custom comments
     *
     * @return string
     */
    public function getFirstName()
    {
        // some custom comment
        return $this->firstName;
    }
}
