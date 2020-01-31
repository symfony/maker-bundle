<?php

namespace App\Entity;

<<<<<<< HEAD
class User
{
    public function __construct()
    {
=======
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    public function __construct()
    {
        //parent::__construct();
    }

    public function getId(): ?int
    {
        return $this->id;
>>>>>>> f724b451 (Adding test case and fix for syntax error when updating __construct())
    }
}
