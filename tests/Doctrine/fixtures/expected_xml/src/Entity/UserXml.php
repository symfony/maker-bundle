<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class UserXml
{
    private $id;

    private ?string $name = null;

    /**
     * @var Collection<int, UserAvatar>
     */
    private Collection $avatars;

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

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, UserAvatar>
     */
    public function getAvatars(): Collection
    {
        return $this->avatars;
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
