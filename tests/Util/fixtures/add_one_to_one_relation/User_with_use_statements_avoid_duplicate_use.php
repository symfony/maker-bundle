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
    private ?\App\OtherEntity\UserProfile $userProfile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserProfile(): ?\App\OtherEntity\UserProfile
    {
        return $this->userProfile;
    }

    public function setUserProfile(?\App\OtherEntity\UserProfile $userProfile): static
    {
        $this->userProfile = $userProfile;

        return $this;
    }
}
