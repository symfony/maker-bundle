<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
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
