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

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
enum EntityIdTypeEnum
{
    case INT;
    case UUID;
    case ULID;
}
