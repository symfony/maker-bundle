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
 * Skips the test when TEST_DATABASE_DSN points the generated apps at
 * PostgreSQL. Meant for scenarios built around identifiers PostgreSQL
 * reserves (like "user"): make:entity itself refuses them as property
 * names there, so the interactive flow legitimately diverges.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class SkipOnPostgres
{
    public function __construct(
        public string $reason = '',
    ) {
    }
}
