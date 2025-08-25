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
    $container->services()

        ->set('maker.file_manager', FileManager::class)
            ->args([
                service('filesystem'),
                service('maker.autoloader_util'),
                service('maker.file_link_formatter'),
                param('kernel.project_dir'),
                param('twig.default_path'),
            ])

        ->set('maker.autoloader_finder', ComposerAutoloaderFinder::class)
            ->args([
                null, // root namespace
            ])

        ->set('maker.autoloader_util', AutoloaderUtil::class)
            ->args([
                service('maker.autoloader_finder'),
            ])

        ->set('maker.file_link_formatter', MakerFileLinkFormatter::class)
            ->args([
                service('debug.file_link_formatter')->nullOnInvalid(),
            ])

        ->set('maker.event_registry', EventRegistry::class)
            ->args([
                service('event_dispatcher'),
            ])

        ->set('maker.console_error_listener', ConsoleErrorSubscriber::class)
            ->tag('kernel.event_subscriber')

        ->set('maker.doctrine_helper', DoctrineHelper::class)
            ->args([
                null, // entity namespace
                service('doctrine')->nullOnInvalid(),
            ])

        ->set('maker.template_linter', TemplateLinter::class)
            ->args([
                env('default::string:MAKER_PHP_CS_FIXER_BINARY_PATH'),
                env('default::string:MAKER_PHP_CS_FIXER_CONFIG_PATH'),
            ])

        ->set('maker.auto_command.abstract', MakerCommand::class)
            ->abstract()
            ->args([
                null, // maker
                service('maker.file_manager'),
                service('maker.generator'),
                service('maker.template_linter'),
            ])

        ->set('maker.generator', Generator::class)
            ->args([
                service('maker.file_manager'),
                null, // root namespace
                null, // PhpCompatUtil
                service('maker.template_component_generator'),
            ])

        ->set('maker.entity_class_generator', EntityClassGenerator::class)
            ->args([
                service('maker.generator'),
                service('maker.doctrine_helper'),
            ])

        ->set('maker.user_class_builder', UserClassBuilder::class)

        ->set('maker.security_config_updater', SecurityConfigUpdater::class)

        ->set('maker.renderer.form_type_renderer', FormTypeRenderer::class)
            ->args([
                service('maker.generator'),
            ])

        ->set('maker.security_controller_builder', SecurityControllerBuilder::class)

        ->set('maker.php_compat_util', PhpCompatUtil::class)
            ->args([
                service('maker.file_manager'),
            ])

        ->set('maker.template_component_generator', TemplateComponentGenerator::class)
            ->args([
                null, // generate_final_classes
                null, // generate_final_entities
                null, // root_namespace
            ])
    ;
};
