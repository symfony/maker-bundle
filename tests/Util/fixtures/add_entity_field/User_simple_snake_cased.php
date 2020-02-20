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
     * @ORM\Column(type="string", name="foo_prop")
     */
    private $fooProp;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFooProp(): ?string
    {
        return $this->fooProp;
    }

    public function setFooProp(string $fooProp): self
    {
        $this->fooProp = $fooProp;

        return $this;
    }
}
