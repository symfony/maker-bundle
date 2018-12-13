<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Embed
{
    /**
     * @ORM\Column(type="integer")
     */
    private $val;

    public function getVal(): ?int
    {
        return $this->val;
    }

    public function setVal(int $val): self
    {
        $this->val = $val;

        return $this;
    }
}
