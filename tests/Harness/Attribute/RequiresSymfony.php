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
 * Skips the test unless the symfony/framework-bundle version installed in the
 * generated app satisfies the constraint (e.g. ">=7.0", "<7.0").
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class RequiresSymfony
{
    public function __construct(
        public string $versionConstraint,
    ) {
    }
}
