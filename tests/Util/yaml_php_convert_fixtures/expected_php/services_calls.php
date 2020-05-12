<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassService;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services->set(SimpleFakeClassService::class)
        ->call('withMailer', [ref('mailer')], false)
        ->call('setLogger', [ref('logger')])
        ->call('setMailer', [ref('mailer')])
        ->call('withMailer', [ref('mailer'), 'argument'], false)
        ->call('setLogger', [ref('logger')])
        ->call('setMailer', [ref('mailer')])
        ->call('withLogger', [ref('logger')], true)
    ;
};
