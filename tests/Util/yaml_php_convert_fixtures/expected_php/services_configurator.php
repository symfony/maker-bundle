<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClass;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassTwo;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $container, Kernel $kernel): void {
    $services = $container->services();

    $services->set(SimpleFakeClass::class)
        ->configurator([service(SimpleFakeClassTwo::class), 'configure'])
    ;

    $services->set(SimpleFakeClassTwo::class)
        ->configurator(service(SimpleFakeClass::class))
    ;
};
