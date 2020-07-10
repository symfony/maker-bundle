<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassService;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassServiceTwo;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $container, Kernel $kernel): void {
    $services = $container->services();

    $services->set('string_service_factory', SimpleFakeClassService::class)
        ->factory(service('factory_service_invokable'))
    ;

    $services->set('array_service_factory', SimpleFakeClassService::class)
        ->factory([service('factory_service'), 'constructFoo'])
    ;

    $services->set('array_static_factory', SimpleFakeClassService::class)
        ->factory([SimpleFakeClassServiceTwo::class, 'create'])
    ;
};
