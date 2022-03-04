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
use Symfony\Bundle\MakerBundle\Util\YamlManipulationFailedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class LoginFormAuthenticatorMaker extends AbstractAuthenticatorMaker
{
    public function getDescription(): string
    {
        return 'Login form';
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): bool
    {
        if ($io->confirm('Do you require other fields than user identifier (e.g. email) and password?', false)) {
            $input->setArgument('authenticator-type', self::AUTH_TYPE_FORM_LOGIN_AUTHENTICATOR);

            return false;
        }

        parent::interact($input, $io, $command);

        $this->askControllerClass($input, $io, $command);
        $this->askUsernameField($input, $io, $command);

        return true;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator, bool $useSecurity52): array
    {
    }
}
