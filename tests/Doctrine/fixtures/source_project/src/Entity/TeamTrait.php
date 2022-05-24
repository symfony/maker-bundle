<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

use Doctrine\ORM\Mapping as ORM;

trait TeamTrait
{
    #[ORM\ManyToMany(targetEntity: User::class)]
    private $members;
}
