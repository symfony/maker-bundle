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

/**
 * Task, without ORM annotations.
 */
class Task
{
    private $id;

    private $task;

    private $dueDate;

    public function getId()
    {
        return $this->id;
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
}
