<?php

namespace Symfony\Bundle\MakerBundle\Tests\Doctrine\fixtures\source_project\src\Entity;

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
     * @ORM\OneToMany(targetEntity="UserAvatar", mappedBy="user")
     */
    private $avatars;

    /**
     * @ORM\OneToOne(targetEntity="UserProfile", mappedBy="user")
     */
    private $userProfile;

    /**
     * @ORM\ManyToMany(targetEntity="Tag")
     */
    private $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->avatars = new ArrayCollection();
    }

    public function getId(): ?int
    {
        // custom comment
        return $this->id;
    }

    public function setUserProfile(?UserProfile $userProfile)
    {
        $this->userProfile = $userProfile;
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

    public function addTag(Tag $tag)
    {
        if ($this->tags->contains($tag)) {
            return;
        }

        $this->tags[] = $tag;
    }

    public function removeTag(Tag $tag)
    {
        if (!$this->tags->contains($tag)) {
            return;
        }

        $this->tags->removeElement($tag);
    }

    // add your own fields
}
