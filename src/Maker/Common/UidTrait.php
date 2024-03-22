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
    protected bool $usesUid = false;

    protected function addWithUuidOption(Command $command): Command
    {
        $command->addOption('with-uuid', 'u', InputOption::VALUE_NONE, 'Use UUID for entity "id"');

        return $command;
    }

    protected function checkIsUsingUid(InputInterface $input): void
    {
        if (($this->usesUid = $input->getOption('with-uuid')) && !class_exists(Uuid::class)) {
            throw new \RuntimeException('You must install symfony/uid to use Uuid\'s as "id" (composer require symfony/uid)');
        }
    }
}
