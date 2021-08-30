<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\DependencyInjection;

use Symfony\Bundle\MakerBundle\DependencyInjection\CompilerPass\MakeCommandRegistrationPass;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class MakerExtension extends Extension
{
    /**
     * @deprecated remove this block when removing make:unit-test and make:functional-test
     */
    private const TEST_MAKER_DEPRECATION_MESSAGE = 'The "%service_id%" service is deprecated, use "maker.maker.make_test" instead.';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('makers.xml');

        /**
         * @deprecated remove this block when removing make:unit-test and make:functional-test
         */
        $deprecParams = method_exists(Definition::class, 'getDeprecation') ? ['symfony/maker-bundle', '1.29', self::TEST_MAKER_DEPRECATION_MESSAGE] : [true, self::TEST_MAKER_DEPRECATION_MESSAGE];
        $container
            ->getDefinition('maker.maker.make_unit_test')
            ->setDeprecated(...$deprecParams);
        $container
            ->getDefinition('maker.maker.make_functional_test')
            ->setDeprecated(...$deprecParams);

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $rootNamespace = trim($config['root_namespace'], '\\');

        $autoloaderFinderDefinition = $container->getDefinition('maker.autoloader_finder');
        $autoloaderFinderDefinition->replaceArgument(0, $rootNamespace);

        $makeCommandDefinition = $container->getDefinition('maker.generator');
        $makeCommandDefinition->replaceArgument(1, $rootNamespace);

        $doctrineHelperDefinition = $container->getDefinition('maker.doctrine_helper');
        $doctrineHelperDefinition->replaceArgument(0, $rootNamespace.'\\Entity');

        $container->registerForAutoconfiguration(MakerInterface::class)
            ->addTag(MakeCommandRegistrationPass::MAKER_TAG);
    }
}
