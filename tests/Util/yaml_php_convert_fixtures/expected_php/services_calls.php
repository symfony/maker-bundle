<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassService;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $configurator, Kernel $kernel): void {
    $services = $configurator->services();

    $services->set(SimpleFakeClassService::class)
        ->call('withMailer', [service('mailer')], false)
        ->call('setLogger', [service('logger')])
        ->call('setMailer', [service('mailer')])
        ->call('withMailer', [service('mailer'), 'argument'], false)
        ->call('setLogger', [service('logger')])
        ->call('setMailer', [service('mailer')])
        ->call('withLogger', [service('logger')], true)
    ;
};
