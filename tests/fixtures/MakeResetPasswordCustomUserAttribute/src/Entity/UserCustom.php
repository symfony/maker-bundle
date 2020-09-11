<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

class UserCustom implements UserInterface
{
    private $emailAddress;

    public function getEmailAddress()
    {
    }

    public function getRoles()
    {
    }

    public function getPassword()
    {
    }

    public function setMyPassword()
    {
    }

    public function getSalt()
    {
    }

    public function getUsername()
    {
    }

    public function eraseCredentials()
    {
    }
}
