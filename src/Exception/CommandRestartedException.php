<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Exception;

/**
 * Stops a maker command whose run was handed over to a new process.
 *
 * @internal
 */
final class CommandRestartedException extends \RuntimeException
{
    public function __construct(
        public readonly int $exitCode,
    ) {
        parent::__construct('The command was restarted in a new process.');
    }
}
