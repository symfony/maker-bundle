<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Receipt
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ingredients;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $steps;
}
