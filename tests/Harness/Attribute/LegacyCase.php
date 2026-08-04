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
 * Provenance marker: records the class-qualified ID of the pre-2026 legacy
 * case a test method descends from (e.g.
 * "MakeEntityTest::it_adds_many_to_one_self_referencing"). During the suite
 * migration a coverage script proved all 176 legacy cases were accounted for;
 * the attributes stay as documentation of that lineage.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class LegacyCase
{
    public function __construct(
        public ?string $id,
    ) {
    }
}
