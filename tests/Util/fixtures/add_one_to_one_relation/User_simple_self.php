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
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User", inversedBy="user", cascade={"persist", "remove"})
     */
    private $embeddedUser;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmbeddedUser(): ?self
    {
        return $this->embeddedUser;
    }

    public function setEmbeddedUser(?self $embeddedUser): self
    {
        $this->embeddedUser = $embeddedUser;

        return $this;
    }
}
