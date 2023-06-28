<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Document;

use Doctrine\ODM\MongoDB\Types\Type;
use UserXml;

class UserAvatar
{
    private ?int $id = null;

    private ?UserXml $user = null;

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user): static
    {
        $this->user = $user;

        return $this;
    }


}
