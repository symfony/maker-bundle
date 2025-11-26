<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Bundle\MakerBundle\Maker\MakeAuthenticator;
use Symfony\Bundle\MakerBundle\Maker\MakeCommand;
use Symfony\Bundle\MakerBundle\Maker\MakeController;
use Symfony\Bundle\MakerBundle\Maker\MakeCrud;
use Symfony\Bundle\MakerBundle\Maker\MakeDockerDatabase;
use Symfony\Bundle\MakerBundle\Maker\MakeEntity;
use Symfony\Bundle\MakerBundle\Maker\MakeFixtures;
use Symfony\Bundle\MakerBundle\Maker\MakeForm;
use Symfony\Bundle\MakerBundle\Maker\MakeFunctionalTest;
use Symfony\Bundle\MakerBundle\Maker\MakeMessengerMiddleware;
use Symfony\Bundle\MakerBundle\Maker\MakeRegistrationForm;
use Symfony\Bundle\MakerBundle\Maker\MakeResetPassword;
use Symfony\Bundle\MakerBundle\Maker\MakeSchedule;
use Symfony\Bundle\MakerBundle\Maker\MakeSerializerEncoder;
use Symfony\Bundle\MakerBundle\Maker\MakeSerializerNormalizer;
use Symfony\Bundle\MakerBundle\Maker\MakeStimulusController;
use Symfony\Bundle\MakerBundle\Maker\MakeSubscriber;
use Symfony\Bundle\MakerBundle\Maker\MakeTest;
use Symfony\Bundle\MakerBundle\Maker\MakeTwigComponent;
use Symfony\Bundle\MakerBundle\Maker\MakeTwigExtension;
use Symfony\Bundle\MakerBundle\Maker\MakeUnitTest;
use Symfony\Bundle\MakerBundle\Maker\MakeUser;
use Symfony\Bundle\MakerBundle\Maker\MakeValidator;
use Symfony\Bundle\MakerBundle\Maker\MakeVoter;
use Symfony\Bundle\MakerBundle\Maker\MakeWebhook;
use Symfony\Bundle\MakerBundle\Maker\Security\MakeCustomAuthenticator;
use Symfony\Bundle\MakerBundle\Maker\Security\MakeFormLogin;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('maker.maker.make_authenticator', MakeAuthenticator::class)
        ->args([
            service('maker.file_manager'),
            service('maker.security_config_updater'),
            service('maker.generator'),
            service('maker.doctrine_helper'),
            service('maker.security_controller_builder'),
        ])
        ->tag('maker.command');

    $services->set('maker.maker.make_command', MakeCommand::class)
        ->tag('maker.command');

    $services->set('maker.maker.make_twig_component', MakeTwigComponent::class)
        ->args([service('maker.file_manager')])
        ->tag('maker.command');

    $services->set('maker.maker.make_controller', MakeController::class)
        ->tag('maker.command');

    $services->set('maker.maker.make_crud', MakeCrud::class)
        ->args([
            service('maker.doctrine_helper'),
            service('maker.renderer.form_type_renderer'),
        ])
        ->tag('maker.command');

    $services->set('maker.maker.make_docker_database', MakeDockerDatabase::class)
        ->args([service('maker.file_manager')])
        ->tag('maker.command');

    $services->set('maker.maker.make_entity', MakeEntity::class)
        ->args([
            service('maker.file_manager'),
            service('maker.doctrine_helper'),
            null,
            service('maker.generator'),
            service('maker.entity_class_generator'),
        ])
        ->tag('maker.command');

    $services->set('maker.maker.make_fixtures', MakeFixtures::class)
        ->tag('maker.command');

    $services->set('maker.maker.make_form', MakeForm::class)
        ->args([
            service('maker.doctrine_helper'),
            service('maker.renderer.form_type_renderer'),
        ])
        ->tag('maker.command');

    $services->set('maker.maker.make_functional_test', MakeFunctionalTest::class)
        ->tag('maker.command')
        ->deprecate('symfony/maker-bundle', '1.29', 'The "%service_id%" service is deprecated, use "maker.maker.make_test" instead.');

    $services->set('maker.maker.make_listener', \Symfony\Bundle\MakerBundle\Maker\MakeListener::class)
        ->args([service('maker.event_registry')])
        ->tag('maker.command');

    $services->set('maker.maker.make_message', \Symfony\Bundle\MakerBundle\Maker\MakeMessage::class)
        ->args([service('maker.file_manager')])
        ->tag('maker.command');

    $services->set('maker.maker.make_messenger_middleware', MakeMessengerMiddleware::class)
        ->tag('maker.command');

    $services->set('maker.maker.make_registration_form', MakeRegistrationForm::class)
        ->args([
            service('maker.file_manager'),
            service('maker.renderer.form_type_renderer'),
            service('maker.doctrine_helper'),
            service('router')->ignoreOnInvalid(),
        ])
        ->tag('maker.command');

    $services->set('maker.maker.make_reset_password', MakeResetPassword::class)
        ->args([
            service('maker.file_manager'),
            service('maker.doctrine_helper'),
            service('maker.entity_class_generator'),
            service('router')->ignoreOnInvalid(),
        ])
        ->tag('maker.command');

    $services->set('maker.maker.make_schedule', MakeSchedule::class)
        ->args([service('maker.file_manager')])
        ->tag('maker.command');

    $services->set('maker.maker.make_serializer_encoder', MakeSerializerEncoder::class)
        ->tag('maker.command');

    $services->set('maker.maker.make_serializer_normalizer', MakeSerializerNormalizer::class)
        ->tag('maker.command');

    $services->set('maker.maker.make_subscriber', MakeSubscriber::class)
        ->args([service('maker.event_registry')])
        ->tag('maker.command')
        ->deprecate('symfony/maker-bundle', '1.51', 'The "%service_id%" service is deprecated, use "maker.maker.make_listener" instead.');

    $services->set('maker.maker.make_twig_extension', MakeTwigExtension::class)
        ->tag('maker.command');

    $services->set('maker.maker.make_test', MakeTest::class)
        ->tag('maker.command');

    $services->set('maker.maker.make_unit_test', MakeUnitTest::class)
        ->tag('maker.command')
        ->deprecate('symfony/maker-bundle', '1.29', 'The "%service_id%" service is deprecated, use "maker.maker.make_test" instead.');

    $services->set('maker.maker.make_validator', MakeValidator::class)
        ->tag('maker.command');

    $services->set('maker.maker.make_voter', MakeVoter::class)
        ->tag('maker.command');

    $services->set('maker.maker.make_user', MakeUser::class)
        ->args([
            service('maker.file_manager'),
            service('maker.user_class_builder'),
            service('maker.security_config_updater'),
            service('maker.entity_class_generator'),
            service('maker.doctrine_helper'),
        ])
        ->tag('maker.command');

    $services->set('maker.maker.make_migration', \Symfony\Bundle\MakerBundle\Maker\MakeMigration::class)
        ->args([
            '%kernel.project_dir%',
            service('maker.file_link_formatter'),
        ])
        ->tag('maker.command');

    $services->set('maker.maker.make_stimulus_controller', MakeStimulusController::class)
        ->tag('maker.command');

    $services->set('maker.maker.make_form_login', MakeFormLogin::class)
        ->args([
            service('maker.file_manager'),
            service('maker.security_config_updater'),
            service('maker.security_controller_builder'),
        ])
        ->tag('maker.command');

    $services->set('maker.maker.make_custom_authenticator', MakeCustomAuthenticator::class)
        ->args([
            service('maker.file_manager'),
            service('maker.generator'),
        ])
        ->tag('maker.command');

    $services->set('maker.maker.make_webhook', MakeWebhook::class)
        ->args([
            service('maker.file_manager'),
            service('maker.generator'),
        ])
        ->tag('maker.command');
};
