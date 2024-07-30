<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Security\Model;

/**
 * @author Jesse Rushlow<jr@rushlow.dev>
 *
 * @internal
 */
final class Authenticator
{
    public function __construct(
        public AuthenticatorType $type,
        public string $firewallName,
        public ?string $authenticatorClass = null,
    ) {
    }

    /**
     * Useful for asking questions like "Which authenticator do you want to use?".
     */
    public function __toString(): string
    {
        return \sprintf(
            '"%s" in the "%s" firewall',
            $this->authenticatorClass ?? $this->type->value,
            $this->firewallName,
        );
    }
}
