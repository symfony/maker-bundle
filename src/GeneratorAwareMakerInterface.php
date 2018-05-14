<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Lets the interact method access to the Generator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface GeneratorAwareMakerInterface extends MakerInterface
{
    /**
     * {@inheritdoc}
     */
    public function interact(InputInterface $input, ConsoleStyle $io, Command $command, Generator $generator = null);
}
