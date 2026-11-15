<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    public function __construct(
        #[ORM\Embedded(class: Address::class)]
        private Address $address,

        /**
         * @var Collection<int, Recipe>
         */
        #[ORM\ManyToMany(targetEntity: Recipe::class, inversedBy: 'foods')]
        private Collection $recipes = new ArrayCollection(),
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
