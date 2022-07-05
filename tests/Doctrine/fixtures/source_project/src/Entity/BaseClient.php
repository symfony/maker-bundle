<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
class BaseClient
{
    use TeamTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    #[ORM\Column(type: Types::STRING)]
    private $name;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private $creator;

    #[ORM\Column(type: Types::INTEGER)]
    private $magic;

    public function __construct()
    {
        $this->magic = 42;
    }

    public function getId()
    {
        return $this->id;
    }
}
