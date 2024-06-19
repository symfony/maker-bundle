<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Some\Other\UserProfile;
use Some\Other\FooCategory as Category;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'user', cascade: ['persist', 'remove'])]
    private ?\App\OtherEntity\Category $category = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?\App\OtherEntity\Category
    {
        return $this->category;
    }

    public function setCategory(?\App\OtherEntity\Category $category): static
    {
        $this->category = $category;

        return $this;
    }
}
