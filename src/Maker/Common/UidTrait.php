<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker\Common;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Uid\Uuid;

/**
 * @author Jesse Rushlow<jr@rushlow.dev>
 *
 * @internal
 */
trait UidTrait
{
    /**
     * Set by calling checkIsUsingUuid().
     * Use in a maker's generate() to determine if entity wants to use uuid's.
     */
    protected bool $usesUid = false;

    /**
     * Call this in a maker's configure() to consistently allow entity's with UUID's.
     */
    protected function addWithUuidOption(Command $command): Command
    {
        $command->addOption('with-uuid', 'u', InputOption::VALUE_NONE, 'Use UUID for entity "id"');

        return $command;
    }

    /**
     * Call this as early as possible in a maker's interact().
     */
    protected function checkIsUsingUid(InputInterface $input): void
    {
        if (($this->usesUid = $input->getOption('with-uuid')) && !class_exists(Uuid::class)) {
            throw new \RuntimeException('You must install symfony/uid to use Uuid\'s as "id" (composer require symfony/uid)');
        }
    }
}
