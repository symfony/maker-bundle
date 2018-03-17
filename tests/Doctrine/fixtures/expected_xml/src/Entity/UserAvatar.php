<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

class UserAvatar
{
    private $id;

    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
