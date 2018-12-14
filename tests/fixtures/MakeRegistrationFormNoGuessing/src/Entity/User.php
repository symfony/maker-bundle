<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity()
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $emailAlt;

    /**
     * @ORM\Column(type="array")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $passwordAlt;

    public function getId()
    {
        return $this->id;
    }

    public function getEmailAlt()
    {
        return $this->emailAlt;
    }

    public function setEmailAlt(string $email): self
    {
        $this->emailAlt = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->emailAlt;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPasswordAlt(): string
    {
        return (string) $this->passwordAlt;
    }

    public function setPasswordAlt(string $password): self
    {
        $this->passwordAlt = $password;

        return $this;
    }

    public function getPassword()
    {
        return $this->passwordAlt;
    }

    public function getSalt()
    {
    }

    public function eraseCredentials()
    {
    }
}
