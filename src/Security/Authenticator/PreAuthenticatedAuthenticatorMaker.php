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

class PreAuthenticatedAuthenticatorMaker extends AbstractAuthenticatorMaker
{
    private const AUTH_TYPE_X509 = 'x509';
    private const AUTH_TYPE_REMOTE_USER = 'remote-user';

    public function getDescription(): string
    {
        return 'Pre authenticated (e.g. Kerebos or certificates)';
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): bool
    {
        $preAuthenticatedTypes = [
            'Remote user (e.g. Kerebos)' => self::AUTH_TYPE_REMOTE_USER,
            'Client certificates (X.509)' => self::AUTH_TYPE_X509,
        ];
        $authenticatorType = $io->choice('Which pre-authenticated method do you want?', array_keys($preAuthenticatedTypes));
        // argument type is changed, but it should still be managed by this maker
        $input->setArgument('authenticator-type', $preAuthenticatedTypes[$authenticatorType]);

        parent::interact($input, $io, $command);

        return true;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator, bool $useSecurity52): array
    {
    }
}
