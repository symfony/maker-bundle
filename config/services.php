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

use Symfony\Bundle\MakerBundle\Command\MakerCommand;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Doctrine\EntityClassGenerator;
use Symfony\Bundle\MakerBundle\Event\ConsoleErrorSubscriber;
use Symfony\Bundle\MakerBundle\EventRegistry;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Security\SecurityConfigUpdater;
use Symfony\Bundle\MakerBundle\Security\SecurityControllerBuilder;
use Symfony\Bundle\MakerBundle\Security\UserClassBuilder;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder;
use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Bundle\MakerBundle\Util\TemplateComponentGenerator;
use Symfony\Bundle\MakerBundle\Util\TemplateLinter;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('maker.file_manager', FileManager::class)
        ->args([
            service('filesystem'),
            service('maker.autoloader_util'),
            service('maker.file_link_formatter'),
            '%kernel.project_dir%',
            '%twig.default_path%',
        ]);

    $services->set('maker.autoloader_finder', ComposerAutoloaderFinder::class)
        ->args(['']);

    $services->set('maker.autoloader_util', AutoloaderUtil::class)
        ->args([service('maker.autoloader_finder')]);

    $services->set('maker.file_link_formatter', MakerFileLinkFormatter::class)
        ->args([service('debug.file_link_formatter')->ignoreOnInvalid()]);

    $services->set('maker.event_registry', EventRegistry::class)
        ->args([service('event_dispatcher')]);

    $services->set('maker.console_error_listener', ConsoleErrorSubscriber::class)
        ->tag('kernel.event_subscriber');

    $services->set('maker.doctrine_helper', DoctrineHelper::class)
        ->args([
            '',
            service('doctrine')->ignoreOnInvalid(),
        ]);

    $services->set('maker.template_linter', TemplateLinter::class)
        ->args([
            '%env(default::string:MAKER_PHP_CS_FIXER_BINARY_PATH)%',
            '%env(default::string:MAKER_PHP_CS_FIXER_CONFIG_PATH)%',
        ]);

    $services->set('maker.auto_command.abstract', MakerCommand::class)
        ->abstract()
        ->args([
            '',
            service('maker.file_manager'),
            service('maker.generator'),
            service('maker.template_linter'),
        ]);

    $services->set('maker.generator', Generator::class)
        ->args([
            service('maker.file_manager'),
            '',
            null,
            service('maker.template_component_generator'),
        ]);

    $services->set('maker.entity_class_generator', EntityClassGenerator::class)
        ->args([
            service('maker.generator'),
            service('maker.doctrine_helper'),
        ]);

    $services->set('maker.user_class_builder', UserClassBuilder::class);

    $services->set('maker.security_config_updater', SecurityConfigUpdater::class);

    $services->set('maker.renderer.form_type_renderer', FormTypeRenderer::class)
        ->args([service('maker.generator')]);

    $services->set('maker.security_controller_builder', SecurityControllerBuilder::class);

    $services->set('maker.php_compat_util', PhpCompatUtil::class)
        ->args([service('maker.file_manager')]);

    $services->set('maker.template_component_generator', TemplateComponentGenerator::class)
        ->args([
            '',
            '',
            '',
        ]);
};
