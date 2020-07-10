<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator, Kernel $kernel): void {
    $services = $configurator->services();

    // default configuration for services in *this* file
    $services->defaults()
        ->autowire()
        ->autoconfigure()
    ;
};
