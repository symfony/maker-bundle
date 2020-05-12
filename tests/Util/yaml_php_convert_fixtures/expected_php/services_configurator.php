<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClass;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassTwo;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services->set(SimpleFakeClass::class)
        ->configurator([ref(SimpleFakeClassTwo::class), 'configure'])
    ;

    $services->set(SimpleFakeClassTwo::class)
        ->configurator(ref(SimpleFakeClass::class))
    ;
};
