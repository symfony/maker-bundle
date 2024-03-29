<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Embed
{
    #[ORM\Column]
    private ?int $val = null;

    public function getVal(): ?int
    {
        return $this->val;
    }

    public function setVal(int $val): static
    {
        $this->val = $val;

        return $this;
    }
}
