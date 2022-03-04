<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Security\Authenticator;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Security\Http\Authenticator\LoginLinkAuthenticator;

class LoginLinkAuthenticatorMaker extends AbstractAuthenticatorMaker
{
    public function getDescription(): string
    {
        return 'Login link';
    }

    public function isAvailable(bool $security52): bool
    {
        return $security52 && class_exists(LoginLinkAuthenticator::class);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator, bool $useSecurity52): array
    {
    }
}
