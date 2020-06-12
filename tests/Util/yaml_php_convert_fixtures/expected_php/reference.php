<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\AnonymousBar;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClass;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassService;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassServiceThree;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassServiceTwo;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services->set(SimpleFakeClassService::class)
        ->args([inline_service(AnonymousBar::class)])
    ;

    $services->set(SimpleFakeClassServiceTwo::class)
        ->args([inline_service(SimpleFakeClass::class)])
    ;

    $services->set(SimpleFakeClassServiceThree::class)
        ->factory([inline_service(AnonymousBar::class), 'constructFoo'])
    ;
};
