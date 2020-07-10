<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassService;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassServiceTwo;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassTwo;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator, Kernel $kernel): void {
    $services = $configurator->services();

    $services->alias(SimpleFakeClassService::class, 'fake.simple_class');

    $services->alias(SimpleFakeClassServiceTwo::class.' $variable', 'App\Fake\Class');

    $services->alias('fake.simple_class_two', SimpleFakeClassTwo::class)
        ->private()
        ->deprecate('The "%alias_id%" alias is deprecated. Do not use it anymore.')
    ;
};
