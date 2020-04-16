<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class User
{
    const FOO = 'bar';

    /**
     * Hi!
     */
    const BAR = 'bar';

    public function __construct()
    {
    }

    /**
     * @return string
     */
    public function hello()
    {
        return 'hi there!';
    }
}
