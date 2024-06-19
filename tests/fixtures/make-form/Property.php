<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Property
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column()]
    private ?int $id = null;

    /**
     * Many Features have One Product.
     */
    #[ORM\ManyToOne(inversedBy: 'properties')]
    #[ORM\JoinColumn(name: 'sour_food_id', referencedColumnName: 'id')]
    private ?SourFood $sourFood = null;

    public function setSourFood(?SourFood $sourFood): static
    {
        $this->sourFood = $sourFood;

        return $this;
    }

    public function getSourFood(): ?SourFood
    {
        return $this->sourFood;
    }
}
