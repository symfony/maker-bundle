<?php

namespace Symfony\Bundle\SecurityBundle\Routing;

use Symfony\Component\Routing\RouteCollection;

class LogoutRouteLoader { public function __invoke() { return new RouteCollection();} }
