<?php

namespace Symfony\Bundle\MakerBundle\Tests\Doctrine\fixtures\source_project\src\Entity;

use Doctrine\ORM\Mapping as ORM;

trait TeamTrait
{
    /**
     * @ORM\ManyToMany(targetEntity="User")
     */
    private $members;
}
