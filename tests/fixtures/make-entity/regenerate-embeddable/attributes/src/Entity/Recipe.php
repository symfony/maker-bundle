<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Recipe
{
    #[ORM\Column()]
    private ?string $ingredients = null;

    #[ORM\Column()]
    private ?string $steps = null;
}
