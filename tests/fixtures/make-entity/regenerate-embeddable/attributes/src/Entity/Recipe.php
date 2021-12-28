<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Recipe
{
    #[ORM\Column(type: Types::STRING, length: 255)]
    private $ingredients;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private $steps;
}
