<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator) {
    $configurator->import('services_simple.yaml', 'annotations', true);
};
