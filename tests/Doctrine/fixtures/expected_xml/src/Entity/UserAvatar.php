<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Entity;

class UserAvatar
{
    private ?int $id = null;

    private ?UserXml $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?UserXml
    {
        return $this->user;
    }

    public function setUser(?UserXml $user): static
    {
        $this->user = $user;

        return $this;
    }
}
