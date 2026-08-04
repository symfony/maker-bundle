<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Application;

/**
 * Why a package is a direct requirement of a profile. Machine-readable so the
 * dependency-declaration contract suite can assert the relationship instead of
 * letting the profile become its own source of truth.
 */
final class Reason
{
    /** A maker's configureDependencies() declares it. */
    public const DECLARED_BY_MAKER = 'declared-by-maker';

    /** A maker would auto-install it at runtime; preinstalled to keep tests offline. */
    public const RUNTIME_INSTALLER = 'runtime-installer';

    /** Needed by the harness itself (phpunit, browser-kit, ...). */
    public const HARNESS_ONLY = 'harness-only';

    /** Needed only by test fixtures or runtime assertions. */
    public const TEST_ONLY = 'test-only';

    /** Deliberately present so a lean guard elsewhere can assert its absence. */
    public const LEAN_GUARD_SUBJECT = 'lean-guard-subject';
}
