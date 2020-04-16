<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests;

use App\Entity\User;
use App\Entity\UserAvatar;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedEntityTest extends KernelTestCase
{
    public function testGeneratedEntity()
    {
        self::bootKernel();

        // sanity checks to make sure the methods/classes regenerated
        $user = new User();
        $avatar = new UserAvatar();
        $user->addAvatar($avatar);

        $this->assertSame($user, $avatar->getUser());
    }
}
