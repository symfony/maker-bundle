<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
class BaseClient
{
    use TeamTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?string $name = null;

    #[ORM\ManyToOne]
    private ?User $creator = null;

    #[ORM\Column()]
    private int $magic;

    public function __construct()
    {
        $this->magic = 42;
    }

    public function getId()
    {
        return $this->id;
    }
}
