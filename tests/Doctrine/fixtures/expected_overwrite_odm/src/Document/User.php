<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;

#[ODM\Document]
class User
{
    #[ODM\Id]
    private ?int $id = null;

    #[ODM\ReferenceMany(targetDocument: UserAvatar::class, mappedBy: 'user')]
    private Collection $avatars;

    #[ODM\ReferenceOne(targetDocument: UserProfile::class, mappedBy: 'user')]
    private ?UserProfile $userProfile = null;

    public function __construct()
    {
        $this->avatars = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function customMethod()
    {
        return '';
    }

    public function setUserProfile(?UserProfile $userProfile): static
    {
        $this->userProfile = $userProfile;

        return $this;
    }

    /**
     * @return Collection<int, UserAvatar>
     */
    public function getAvatars(): Collection
    {
        return $this->avatars;
    }

    public function setAvatars($avatars): static
    {
        $this->avatars = $avatars;

        return $this;
    }

    public function getUserProfile(): ?UserProfile
    {
        return $this->userProfile;
    }

    public function addAvatar(UserAvatar $avatar): static
    {
        if (!$this->avatars->contains($avatar)) {
            $this->avatars->add($avatar);
            $avatar->setUser($this);
        }

        return $this;
    }

    public function removeAvatar(UserAvatar $avatar): static
    {
        if ($this->avatars->removeElement($avatar)) {
            // set the owning side to null (unless already changed)
            if ($avatar->getUser() === $this) {
                $avatar->setUser(null);
            }
        }

        return $this;
    }
}
