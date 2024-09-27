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

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * @author Jesse Rushlow<jr@rushlow.dev>
 *
 * @internal
 */
trait UidTrait
{
    private bool $usesUuid = false;
    private bool $usesUlid = false;

    /**
     * Call this in a maker's configure() to consistently allow entity's with UUID's.
     * This should be called after you calling "setHelp()" in the maker.
     */
    protected function addWithUuidOption(Command $command): Command
    {
        $uidHelp = file_get_contents(\dirname(__DIR__, 3).'/config/help/_WithUid.txt');
        $help = $command->getHelp()."\n".$uidHelp;

        $command
            ->addOption(name: 'with-uuid', mode: InputOption::VALUE_NONE, description: 'Use UUID for entity "id"')
            ->addOption('with-ulid', mode: InputOption::VALUE_NONE, description: 'Use ULID for entity "id"')
            ->setHelp($help)
        ;

        return $command;
    }

    /**
     * Call this as early as possible in a maker's interact().
     */
    protected function checkIsUsingUid(InputInterface $input): void
    {
        if (($this->usesUuid = $input->getOption('with-uuid')) && !class_exists(Uuid::class)) {
            throw new RuntimeCommandException('You must install symfony/uid to use Uuid\'s as "id" (composer require symfony/uid)');
        }

        if (($this->usesUlid = $input->getOption('with-ulid')) && !class_exists(Ulid::class)) {
            throw new RuntimeCommandException('You must install symfony/uid to use Ulid\'s as "id" (composer require symfony/uid)');
        }

        if ($this->usesUuid && $this->usesUlid) {
            throw new RuntimeCommandException('Setting --with-uuid & --with-ulid at the same time is not allowed. Please choose only one.');
        }
    }

    protected function getIdType(): EntityIdTypeEnum
    {
        if ($this->usesUuid) {
            return EntityIdTypeEnum::UUID;
        }

        if ($this->usesUlid) {
            return EntityIdTypeEnum::ULID;
        }

        return EntityIdTypeEnum::INT;
    }
}
