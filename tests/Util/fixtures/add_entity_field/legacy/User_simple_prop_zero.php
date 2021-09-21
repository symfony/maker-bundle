<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="decimal", precision=6, scale=0)
     */
    private $decimal;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDecimal(): ?string
    {
        return $this->decimal;
    }

    public function setDecimal(string $decimal): self
    {
        $this->decimal = $decimal;

        return $this;
    }
}
