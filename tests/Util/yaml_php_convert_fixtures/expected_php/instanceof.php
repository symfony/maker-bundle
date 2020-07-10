<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassService;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassServiceTwo;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator, Kernel $kernel): void {
    $services = $configurator->services();

    $services->instanceof(SimpleFakeClassService::class)
        ->public()
        ->tag('app.domain_loader')
    ;

    $services->instanceof(SimpleFakeClassServiceTwo::class)
        ->private()
        ->tag('app.domain_loader')
    ;
};
