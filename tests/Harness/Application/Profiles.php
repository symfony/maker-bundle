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
 * Names of the application profiles tests can declare. The definitions live in
 * ProfileCatalog: one rich MEGA app most tests share, plus a few lean guards
 * whose deliberate ABSENCES are the point.
 */
final class Profiles
{
    public const BASE = 'base';
    public const MEGA = 'mega';
    public const MEGA_CUSTOM_NAMESPACE = 'mega-custom-namespace';
    public const GUARD = 'guard';
    public const I18N = 'i18n';
    public const PANTHER = 'panther';
    public const PHPCSFIXER = 'phpcsfixer';
}
