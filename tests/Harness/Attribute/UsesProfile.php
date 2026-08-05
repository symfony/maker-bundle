<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Attribute;

/**
 * Overrides the class-level profile for a single test method.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class UsesProfile
{
    public function __construct(
        public string $profile,
    ) {
    }
}
