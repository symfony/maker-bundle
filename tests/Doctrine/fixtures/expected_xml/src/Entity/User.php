<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class User
{
    private $id;

    private $name;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|UserAvatar[]
     */
    public function getAvatars(): Collection
    {
        return $this->avatars;
    }

    public function addAvatar(UserAvatar $avatar): self
    {
        if (!$this->avatars->contains($avatar)) {
            $this->avatars[] = $avatar;
            $avatar->setUser($this);
        }

        return $this;
    }

    public function removeAvatar(UserAvatar $avatar): self
    {
        if ($this->avatars->contains($avatar)) {
            $this->avatars->removeElement($avatar);
            // set the owning side to null (unless already changed)
            if ($avatar->getUser() === $this) {
                $avatar->setUser(null);
            }
        }

        return $this;
    }
}
