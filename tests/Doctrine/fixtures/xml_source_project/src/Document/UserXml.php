<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Document;

class UserXml
{
    private $id;
    private $name;
    private $avatars;

    public function getId(): ?int
    {
        return $this->id;
    }
}
