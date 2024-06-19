<?php

namespace App\Entity;

use App\Entity\Category;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\ManyToOne(inversedBy: 'foods')]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'foods')]
    private ?\App\Entity\SubDirectory\Category $subCategory = null;

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getSubCategory(): ?\App\Entity\SubDirectory\Category
    {
        return $this->subCategory;
    }

    public function setSubCategory(?\App\Entity\SubDirectory\Category $subCategory): static
    {
        $this->subCategory = $subCategory;

        return $this;
    }
}
