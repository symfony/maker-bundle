<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClass;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassService;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassTwo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator, Kernel $kernel): void {
    $services = $configurator->services();

    $services->set(SimpleFakeClass::class)
        ->decorate('App\Mailer')
    ;

    $services->set(SimpleFakeClassTwo::class)
        ->decorate(
            'App\Mailer',
            'App\DecoratingMailer.wooz',
            5,
            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE
        )
    ;

    $services->set(SimpleFakeClassService::class)
        ->decorate(
            'App\Mailer',
            null,
            0,
            ContainerInterface::IGNORE_ON_INVALID_REFERENCE
        )
    ;
};
