<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Entity;

class UserXml
{
    private $id;

    public function getId(): ?int
    {
        // custom comment
        return $this->id;
    }
}
