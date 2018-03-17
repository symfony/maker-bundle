<?php

namespace App\Entity;

use Foo\Entity\Category;

class User
{
    /**
     * @ORM\ManyToOne(targetEntity="Foo\Entity\Category", inversedBy="foods")
     */
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
