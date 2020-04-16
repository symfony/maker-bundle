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
    /**
     * test comment on public method
     */
    public function testAddNewMethod(string $someParam): ?string
    {
        $this->someParam = $someParam;
    }
}
