<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $container, Kernel $kernel): void {
    $container->import(__DIR__ . '/services_simple.yaml', 'annotations', true);
};
