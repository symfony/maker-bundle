<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class Task
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $id;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    private $group;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    private $task;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dueDate;

    public function __construct()
    {
        $this->id = uniqid();
    }

    /**
     * Get the value of task.
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * Set the value of task.
     *
     * @return self
     */
    public function setTask($task)
    {
        $this->task = $task;

        return $this;
    }

    /**
     * Get the value of dueDate.
     */
    public function getDueDate()
    {
        return $this->dueDate;
    }

    /**
     * Set the value of dueDate.
     *
     * @return self
     */
    public function setDueDate($dueDate)
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    /**
     * Get the value of group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set the value of group
     *
     * @return  self
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
