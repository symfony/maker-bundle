<?php

namespace App\Entity;

class User
{
    public function setFooProp(string $fooProp): static
    {
        $this->fooProp = $fooProp;

        return $this;
    }
}
