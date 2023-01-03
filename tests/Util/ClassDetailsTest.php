<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Bundle\MakerBundle\Util\ClassDetails;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
final class ClassDetailsTest extends TestCase
{
    public function testHasAttribute(): void
    {
        self::assertTrue((new ClassDetails(FixtureClassDetails::class))->hasAttribute(UniqueEntity::class));

        self::assertFalse((new ClassDetails(__CLASS__))->hasAttribute(UniqueEntity::class));
    }
}

#[UniqueEntity]
final class FixtureClassDetails
{
}
