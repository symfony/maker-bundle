<?php

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
