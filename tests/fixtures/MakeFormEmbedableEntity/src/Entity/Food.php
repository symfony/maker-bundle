<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Food
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Embedded(class="App\Entity\Recept")
     */
    private $recept;

    public function __construct()
    {
        $this->recept = new Recept();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return Recept
     */
    public function getRecept()
    {
        return $this->recept;
    }

    /**
     * @param Recept $recept
     */
    public function setRecept(Recept $recept)
    {
        $this->recept = $recept;
    }
}
