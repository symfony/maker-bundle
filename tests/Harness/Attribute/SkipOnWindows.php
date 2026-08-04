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
 * Portable Windows skip (works under every outer PHPUnit version, unlike the
 * native #[RequiresOperatingSystemFamily]).
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class SkipOnWindows
{
    public function __construct(
        public string $reason = '',
    ) {
    }
}
