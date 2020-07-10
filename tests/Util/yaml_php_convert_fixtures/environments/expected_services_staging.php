<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $container) {
    $services = $container->services();

    // default configuration for services in *this* file
    $services->defaults()
        ->autowire()
        ->autoconfigure()
    ;
};
