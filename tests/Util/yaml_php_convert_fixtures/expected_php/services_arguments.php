<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Kernel;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassService;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassServiceThree;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassServiceTwo;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassThree;
use Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass\SimpleFakeClassTwo;

/*
 * This file is the entry point to configure your own services.
 */
return function (ContainerConfigurator $container, Kernel $kernel): void {
    $services = $container->services();

    $services->alias(SimpleFakeClassServiceTwo::class.' $shoutyTransformer', 'App\Util\UppercaseTransformer');

    $services->set(SimpleFakeClassService::class)
        ->args([
            service('string'),
            ['isaevsgdbfhhnth', 1234561456545, 'jean@vgbsetgil.com'],
            456,
            [
                'App\FooCommand' => service('app.command_handler.foo'),
                'App\BarCommand' => service('app.command_handler.bar'),
            ],
            tagged_locator('app.handler', 'key'),
            tagged_iterator('app.handler'),
        ])
    ;

    $services->set(SimpleFakeClassTwo::class)
        ->args([tagged_locator('app.handler', 'key', 'myOwnMethodName')])
    ;

    $services->set(SimpleFakeClassServiceThree::class)
        ->arg('$fake1', service('id.fake.service'))
        ->arg('$fake2', ['fake_argument', 123, 'jean@mail.com'])
    ;

    $services->set('site_update_manager.normal_users', SimpleFakeClassThree::class)
        ->args([expr('service("App\Mail\MailerConfiguration").getMailerMethod()')])
    ;
};
