<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

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
     * @ORM\OneToMany(targetEntity=UserAvatar::class, mappedBy="user")
     */
    private $avatars;

    /**
     * @ORM\OneToOne(targetEntity=UserProfile::class, mappedBy="user")
     */
    private $userProfile;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class)
     */
    private $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->avatars = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function customMethod()
    {
        return '';
    }

    public function setUserProfile(?UserProfile $userProfile): self
    {
        // unset the owning side of the relation if necessary
        if ($userProfile === null && $this->userProfile !== null) {
            $this->userProfile->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($userProfile !== null && $userProfile->getUser() !== $this) {
            $userProfile->setUser($this);
        }

        $this->userProfile = $userProfile;

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
        if ($this->avatars->removeElement($avatar)) {
            // set the owning side to null (unless already changed)
            if ($avatar->getUser() === $this) {
                $avatar->setUser(null);
            }
        }

        return $this;
    }

    public function getUserProfile(): ?UserProfile
    {
        return $this->userProfile;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
