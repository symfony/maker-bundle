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

    private $fooProp;

    /**
     * @return string
     */
    public function hello()
    {
        return 'hi there!';
    }
}
