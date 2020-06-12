<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassService;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services->set('fake.service.old_syntax', SimpleFakeClassService::class)
        ->deprecate('The fake.service.old_syntax service is deprecated');

    $services->set('fake.service.new_syntax', SimpleFakeClassService::class)
        ->deprecate('symfony/foo-bar', '2.1', 'The fake.service.new_syntax service is deprecated');
};
