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

interface AuthenticatorMakerInterface
{
    public function getDescription(): string;
    public function isAvailable(bool $security52): bool;
    /** @return bool false if the authenticator type changed, true if this authenticator maker should be used in the rest of the maker */
    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): bool;
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator, bool $useSecurity52): array;
}
