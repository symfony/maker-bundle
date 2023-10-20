<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util;

/**
 * @internal
 */
trait FeatureSupportTrait
{
    private $supportLogoutRouteLoader;

    public function forceSupportLogoutRouteLoader(): void
    {
        $this->supportLogoutRouteLoader = true;
    }

    public function forceNotSupportLogoutRouteLoader(): void
    {
        $this->supportLogoutRouteLoader = false;
    }

    public function supportsLogoutRouteLoader(): bool
    {
        return $this->supportLogoutRouteLoader ?? class_exists('Symfony\Bundle\SecurityBundle\Routing\LogoutRouteLoader');
    }
}
