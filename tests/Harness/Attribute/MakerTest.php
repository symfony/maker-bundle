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

#[\Attribute(\Attribute::TARGET_CLASS)]
final class MakerTest
{
    /**
     * @param class-string $maker
     */
    public function __construct(
        public string $maker,
        public string $profile,
    ) {
    }
}
