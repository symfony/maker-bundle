<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
// extra space to keep things interesting
{
    /**
     * @var string
     * @internal
     */
    private $fooProp;

    public function hello()
    {
        return 'hi there!';
    }
}
