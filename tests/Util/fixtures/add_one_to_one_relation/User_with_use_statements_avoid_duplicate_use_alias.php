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
     * @ORM\OneToOne(targetEntity="App\OtherEntity\Category", inversedBy="user", cascade={"persist", "remove"})
     */
    private $category;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?\App\OtherEntity\Category
    {
        return $this->category;
    }

    public function setCategory(?\App\OtherEntity\Category $category): self
    {
        $this->category = $category;

        return $this;
    }
}
