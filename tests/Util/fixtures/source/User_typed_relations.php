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
}
