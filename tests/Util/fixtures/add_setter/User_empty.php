<?php

namespace App\Entity;

class User
{
    public function setFooProp(string $fooProp): self
    {
        $this->fooProp = $fooProp;

        return $this;
    }
}
