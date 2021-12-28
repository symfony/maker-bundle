<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    #[ORM\Column]
    private $firstName;

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
