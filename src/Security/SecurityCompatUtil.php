<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Security;

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class SecurityCompatUtil
{
    public function getPasswordEncoderClassNameDetails(): ClassNameDetails
    {
        if (interface_exists(UserPasswordHasherInterface::class)) {
            return new ClassNameDetails(UserPasswordHasherInterface::class, Str::getNamespace(UserPasswordHasherInterface::class));
        }

        return new ClassNameDetails(UserPasswordEncoderInterface::class, Str::getNamespace(UserPasswordEncoderInterface::class));
    }
}
