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

class User
{
    public function setFooProp(string $fooProp): self
    {
        $this->fooProp = $fooProp;

        return $this;
    }
}
