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
     * @ORM\Column(type="object")
     */
    private $someObject;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSomeObject()
    {
        return $this->someObject;
    }

    public function setSomeObject($someObject): self
    {
        $this->someObject = $someObject;

        return $this;
    }
}
