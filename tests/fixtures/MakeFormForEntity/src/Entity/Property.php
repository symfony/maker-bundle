<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Property
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Many Features have One Product.
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\SourFood", inversedBy="properties")
     * @ORM\JoinColumn(name="sour_food_id", referencedColumnName="id")
     */
    private $sourFood;
}
