<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator, Kernel $kernel): void {
    $services = $configurator->services();

    // makes classes in src/ available to be used as services
    // this creates a service per class whose id is the fully-qualified class name
    $services->load('App\\', __DIR__.'/../src/*')
        ->exclude('../src/{DependencyInjection,Entity,Migrations,Tests,Kernel.php}')
    ;

    // controllers are imported separately to make sure services can be injected
    // as action arguments even if you don't extend any base controller class
    $services->load('App\\Controller\\', __DIR__.'/../src/Controller')
        ->tag('controller.service_arguments')
        ->exclude(['../src/Controller/SomeFile.php', '../src/Controller/OtherFile.php'])
    ;

    $services->load('App\\Domain\\', __DIR__.'/../src/Domain/*/CommandHandler')
        ->tag('command_handler')
    ;

    $services->load('App\\Domain\\', __DIR__.'/../src/Domain/*/EventSubscriber')
        ->tag('event_subscriber')
    ;
};
