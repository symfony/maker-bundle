<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassService;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassTwo;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services->set(SimpleFakeClassTwo::class)
        ->tag('app.mail_transport')
    ;

    $services->set(SimpleFakeClassService::class)
        ->tag('app.mail_transport', ['alias' => 'sendmail'])
        ->tag('app.mail_sender', ['alias' => 'sender', 'priority' => 512])
    ;
};
