<?php

namespace App\Entity;

use Foo\Entity\Category;

class User
{
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'foods')]
    private $category;

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }
}
