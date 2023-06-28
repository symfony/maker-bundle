<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Types\Type;
use UserAvatar;

class UserXml
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
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAvatars()
    {
        return $this->avatars;
    }

    public function setAvatars($avatars): static
    {
        $this->avatars = $avatars;

        return $this;
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
