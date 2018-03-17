<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
// extra space to keep things interesting
{
    public function hello()
    {
        return 'hi there!';
    }

    /**
     * @param string $fooProp
     * @internal
     */
    public function setFooProp(?string $fooProp): self
    {
        $this->fooProp = $fooProp;

        return $this;
    }
}
