<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
#[ORM\Entity]
class Food
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private $id;

    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    private $title;

    /**
     * @ORM\Embedded(class=Recipe::class)
     */
    #[ORM\Embedded(class: Recipe::class)]
    private $recipe;
}
