<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Food
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    private $id;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255)]
    private $title;

    #[ORM\Embedded(class: Recipe::class)]
    private $recipe;
}
