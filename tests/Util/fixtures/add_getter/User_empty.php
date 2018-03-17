<?php

namespace App\Entity;

class User
{
    public function getFooProp(): ?string
    {
        return $this->fooProp;
    }
}
