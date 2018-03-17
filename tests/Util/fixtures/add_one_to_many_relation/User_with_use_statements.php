<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Some\Other\UserProfile;
use Some\Other\FooCategory as Category;

/**
 * @ORM\Entity()
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserAvatarPhoto", mappedBy="user")
     */
    private $avatarPhotos;

    public function __construct()
    {
        $this->avatarPhotos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|UserAvatarPhoto[]
     */
    public function getAvatarPhotos(): Collection
    {
        return $this->avatarPhotos;
    }

    public function addAvatarPhoto(UserAvatarPhoto $avatarPhoto): self
    {
        if (!$this->avatarPhotos->contains($avatarPhoto)) {
            $this->avatarPhotos[] = $avatarPhoto;
            $avatarPhoto->setUser($this);
        }

        return $this;
    }

    public function removeAvatarPhoto(UserAvatarPhoto $avatarPhoto): self
    {
        if ($this->avatarPhotos->contains($avatarPhoto)) {
            $this->avatarPhotos->removeElement($avatarPhoto);
            // set the owning side to null (unless already changed)
            if ($avatarPhoto->getUser() === $this) {
                $avatarPhoto->setUser(null);
            }
        }

        return $this;
    }
}
