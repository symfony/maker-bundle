<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $container, Kernel $kernel): void {
    $services = $container->services();

    // makes classes in src/ available to be used as services
    // this creates a service per class whose id is the fully-qualified class name
    $services->load('App\\', __DIR__ . '/../src/*')
        ->exclude(__DIR__ . '/../src/{DependencyInjection,Entity,Migrations,Tests,Kernel.php}')
    ;

    // controllers are imported separately to make sure services can be injected
    // as action arguments even if you don't extend any base controller class
    $services->load('App\\Controller\\', __DIR__ . '/../src/Controller')
        ->exclude([__DIR__ . '/../src/Controller/SomeFile.php', __DIR__ . '/../src/Controller/OtherFile.php'])
        ->tag('controller.service_arguments')
    ;

    $services->load('App\\Domain\\', __DIR__ . '/../src/Domain/*/CommandHandler')
        ->tag('command_handler')
    ;

    $services->load('App\\Domain\\', __DIR__ . '/../src/Domain/*/EventSubscriber')
        ->tag('event_subscriber')
    ;
};
