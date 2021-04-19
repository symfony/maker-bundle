<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

use Doctrine\Common\Collections\ArrayCollection;
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
     * @ORM\OneToMany(targetEntity=UserAvatar::class, mappedBy="user")
     */
    private $avatars;

    /**
     * @ORM\OneToOne(targetEntity=UserProfile::class, mappedBy="user")
     */
    private $userProfile;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class)
     */
    private $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        // custom comment
        return $this->id;
    }

    public function customMethod()
    {
        return '';
    }

    public function setUserProfile(?UserProfile $userProfile)
    {
        $this->userProfile = $userProfile;
    }
}
