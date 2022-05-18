<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
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
