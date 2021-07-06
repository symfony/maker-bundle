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

/**
 * @ORM\Entity()
 */
class Task
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $task;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $iCanGetNo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $iCanSetNo;

    public function getId()
    {
        return $this->id;
    }

    /**
     * Commented out for the test.
     * We want the setter/getter to be missing.
     */
    // /**
    //  * Get the value of task.
    //  */
    // public function getTask()
    // {
    //     return $this->task;
    // }

    // /**
    //  * Set the value of task.
    //  *
    //  * @return self
    //  */
    // public function setTask($task)
    // {
    //     $this->task = $task;

    //     return $this;
    // }

    /**
     * Commented out for the test.
     * We want the getter to be missing.
     */
    // /**
    //  * Get the value of iCanGetNo
    //  */
    // public function getICanGetNo()
    // {
    //     return $this->iCanGetNo;
    // }

    /**
     * Set the value of iCanGetNo.
     *
     * @return self
     */
    public function setICanGetNo($iCanGetNo)
    {
        $this->iCanGetNo = $iCanGetNo;

        return $this;
    }

    /**
     * Commented out for the test.
     * We want the setter to be missing.
     */
    // /**
    //  * Set the value of iCanSetNo.
    //  *
    //  * @return self
    //  */
    // public function setICanSetNo($iCanSetNo)
    // {
    //     $this->iCanSetNo = $iCanSetNo;

    //     return $this;
    // }

    /**
     * Get the value of iCanSetNo.
     */
    public function getICanSetNo()
    {
        return $this->iCanSetNo;
    }
}
