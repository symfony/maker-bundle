<?php

namespace App\Entity;

class User
{
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
