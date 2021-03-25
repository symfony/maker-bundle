<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Entity;

class UserAvatar
{
    private $id;

    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?UserXml
    {
        return $this->user;
    }

    public function setUser(?UserXml $user): self
    {
        $this->user = $user;

        return $this;
    }
}
