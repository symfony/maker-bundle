<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

class User
{
    private $id;

    public function getId(): ?int
    {
        // custom comment
        return $this->id;
    }
}
