<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

interface MakerInterface
{
    public static function getCommandName(): string;

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void;

    public function configureDependencies(DependencyBuilder $dependencies): void;

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void;

    public function getParameters(InputInterface $input): array;

    public function getFiles(array $params): array;

    public function writeNextStepsMessage(array $params, ConsoleStyle $io): void;
}
