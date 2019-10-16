<?php

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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstName;

    private $photos = [];

    private $courses;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity="UserAvatar", mappedBy="user")
     */
    private $avatars;

    public function getId()
    {
        return $this->id;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    public function getPhotos(): array
    {
        return $this->photos;
    }

    public function setPhotos(array $photos): self
    {
        $this->photos = $photos;

        return $this;
    }

    public function getCourses()
    {
        return $this->courses;
    }

    public function setCourses($courses)
    {
        $this->courses = $courses;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getAvatars()
    {
        return $this->avatars;
    }

    /**
     * @param mixed $avatars
     */
    public function setAvatars($avatars)
    {
        $this->avatars = $avatars;
    }
}
