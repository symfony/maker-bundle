<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $container, Kernel $kernel): void {
    // Put parameters here that don't need to change on each machine where the app is deployed.
    // https://symfony.com/doc/current/best_practices/configuration.html#application-related-configuration
    $parameters = $container->parameters();
    $parameters->set('app.admin_email', 'something@example.com');
    $parameters->set('app.enable_v2_protocol', true);
    $parameters->set('app.supported_locales', ['en', 'es', 'fr']);
    $parameters->set('app.some_parameter', 'This is a Bell char ');
    $parameters->set('my_multilang.language_fallback', ['en' => ['en', 'fr'], 'fr' => ['fr', 'en']]);
};
