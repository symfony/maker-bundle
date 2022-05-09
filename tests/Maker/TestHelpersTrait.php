<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Doctrine\ORM\Mapping\Driver\AttributeReader;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
trait TestHelpersTrait
{
    // @legacy - remove when annotations are no longer supported
    protected function useAttributes(MakerTestRunner $runner): bool
    {
        return \PHP_VERSION_ID >= 80000
            && $runner->doesClassExist(AttributeReader::class);
    }
}
