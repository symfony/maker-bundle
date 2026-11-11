<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'foods')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\ManyToOne]
    private Category $mainCategory;

    #[ORM\OneToOne]
    private UserProfile $userProfile;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getMainCategory(): Category
    {
        return $this->mainCategory;
    }

    public function setMainCategory(Category $mainCategory): static
    {
        $this->mainCategory = $mainCategory;

        return $this;
    }

    public function getUserProfile(): UserProfile
    {
        return $this->userProfile;
    }

    public function setUserProfile(UserProfile $userProfile): static
    {
        $this->userProfile = $userProfile;

        return $this;
    }
}
