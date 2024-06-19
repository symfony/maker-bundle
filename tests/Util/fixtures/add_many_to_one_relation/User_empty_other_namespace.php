<?php

namespace App\Entity;

use Foo\Entity\Category;

class User
{
    #[ORM\ManyToOne(inversedBy: 'foods')]
    private ?Category $category = null;

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }
}
