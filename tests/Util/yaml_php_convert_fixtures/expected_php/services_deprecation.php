<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassService;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $container, Kernel $kernel): void {
    $services = $container->services();

    $services->set('fake.service.old_syntax', SimpleFakeClassService::class)
        ->deprecate('The %service_id% service is deprecated')
    ;

    $services->set('fake.service.new_syntax', SimpleFakeClassService::class)
        ->deprecate('symfony/foobar', '2.1', 'The %service_id% service is deprecated')
    ;
};
