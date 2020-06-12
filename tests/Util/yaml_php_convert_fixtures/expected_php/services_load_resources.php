<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    // makes classes in src/ available to be used as services
    // this creates a service per class whose id is the fully-qualified class name
    $services->load('App\\', '../src/*')
        ->exclude('../src/{DependencyInjection,Entity,Migrations,Tests,Kernel.php}')
    ;

    // controllers are imported separately to make sure services can be injected
    // as action arguments even if you don't extend any base controller class
    $services->load('App\\Controller\\', '../src/Controller')
        ->tag('controller.service_arguments')
    ;

    $services->load('App\\Domain\\', '../src/Domain/*/CommandHandler')
        ->tag('command_handler')
    ;

    $services->load('App\\Domain\\', '../src/Domain/*/EventSubscriber')
        ->tag('event_subscriber')
    ;
};
