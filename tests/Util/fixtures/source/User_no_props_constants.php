<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    /**
     * @return string
     */
    public function hello()
    {
        return 'hi there!';
    }
}
