<?php

namespace App\Entity;

use App\TestTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    use TestTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
