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
 * Declares that the test writes into the app's vendor/ directory. A persistent
 * marker is written at setUp (outside every reset target) so the next reset of
 * the worker app restores vendor/ in full and reapplies the bundle projection,
 * even if the test crashed before teardown.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class DirtiesVendor
{
}
