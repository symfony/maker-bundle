<?php

namespace Symfony\Bundle\MakerBundle\Tests\Doctrine\fixtures\source_project\src\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class UserAvatar
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="avatars", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;
}
