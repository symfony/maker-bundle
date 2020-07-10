<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator, Kernel $kernel): void {
    $configurator->import('services_simple.yaml', 'annotations', true);
};
