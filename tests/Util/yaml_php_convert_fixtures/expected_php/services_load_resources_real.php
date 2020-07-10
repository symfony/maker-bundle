<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator, Kernel $kernel): void {
    $services = $configurator->services();

    $services->load('Symfony\\Bundle\\MakerBundle\\Tests\\Util\\yaml_php_convert_fixtures\\FakeClass\\', __DIR__.'/../FakeClass');
};
