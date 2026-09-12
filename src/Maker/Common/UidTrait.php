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
     *
     * Only performs the checks to fail fast; generation reads the options from
     * the input directly, so that they also apply under --no-interaction.
     */
    protected function checkIsUsingUid(InputInterface $input): void
    {
        $this->getIdType($input);
    }

    protected function getIdType(InputInterface $input): EntityIdTypeEnum
    {
        $hasUuid = $input->getOption('with-uuid');
        $hasUlid = $input->getOption('with-ulid');

        if ($hasUuid && $hasUlid) {
            throw new RuntimeCommandException('Setting --with-uuid & --with-ulid at the same time is not allowed. Please choose only one.');
        }

        if ($hasUuid) {
            if (!class_exists(Uuid::class)) {
                throw new RuntimeCommandException('You must install symfony/uid to use Uuid\'s as "id" (composer require symfony/uid).');
            }

            return EntityIdTypeEnum::UUID;
        }

        if ($hasUlid) {
            if (!class_exists(Ulid::class)) {
                throw new RuntimeCommandException('You must install symfony/uid to use Ulid\'s as "id" (composer require symfony/uid).');
            }

            return EntityIdTypeEnum::ULID;
        }

        return EntityIdTypeEnum::INT;
    }
}
