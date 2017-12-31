<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class User
{
    private $id;

    private $avatars;

    public function __construct()
    {
        $this->avatars = new ArrayCollection();
    }

    public function getId(): ?int
    {
        // custom comment
        return $this->id;
    }

    /**
     * @return Collection|UserAvatar[]
     */
    public function getAvatars(): Collection
    {
        return $this->avatars;
    }

    public function addAvatar(UserAvatar $avatar)
    {
        if ($this->avatars->contains($avatar)) {
            return;
        }

        $this->avatars[] = $avatar;
        $avatar->setUser($this);
    }

    public function removeAvatar(UserAvatar $avatar)
    {
        if (!$this->avatars->contains($avatar)) {
            return;
        }

        $this->avatars->removeElement($avatar);
        // set the owning side to null (unless already changed)
        if ($avatar->getUser() === $this) {
            $avatar->setUser(null);
        }
    }

    // add your own fields
}
