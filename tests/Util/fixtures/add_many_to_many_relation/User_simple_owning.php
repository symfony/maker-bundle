<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
     * @ORM\ManyToMany(targetEntity="App\Entity\Recipe", inversedBy="foods")
     */
    private $recipes;

    public function __construct()
    {
        $this->recipes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Recipe[]
     */
    public function getRecipes(): Collection
    {
        return $this->recipes;
    }

    public function addRecipe(Recipe $recipe)
    {
        if ($this->recipes->contains($recipe)) {
            return;
        }

        $this->recipes[] = $recipe;
    }

    public function removeRecipe(Recipe $recipe)
    {
        if (!$this->recipes->contains($recipe)) {
            return;
        }

        $this->recipes->removeElement($recipe);
    }

    // add your own fields
}
