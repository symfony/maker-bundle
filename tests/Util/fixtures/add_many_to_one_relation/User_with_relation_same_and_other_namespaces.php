<?php

namespace App\Entity;

use App\Entity\Category;

class User
{
    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="foods")
     */
    private $category;

    /**
     * @ORM\ManyToOne(targetEntity=\App\Entity\SubDirectory\Category::class, inversedBy="foods")
     */
    private $subCategory;

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getSubCategory(): ?\App\Entity\SubDirectory\Category
    {
        return $this->subCategory;
    }

    public function setSubCategory(?\App\Entity\SubDirectory\Category $subCategory): self
    {
        $this->subCategory = $subCategory;

        return $this;
    }
}
