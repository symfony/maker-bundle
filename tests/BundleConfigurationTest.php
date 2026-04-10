<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Loader\DefinitionFileLoader;
use Symfony\Component\Config\Definition\Processor;

class BundleConfigurationTest extends TestCase
{
    public function testDefaultConfiguration()
    {
        $config = $this->processConfiguration([]);

        $this->assertSame('App', $config['root_namespace']);
        $this->assertTrue($config['generate_final_classes']);
        $this->assertFalse($config['generate_final_entities']);
        $this->assertSame([], $config['namespaces']);
    }

    public function testAllOptionsConfigured()
    {
        $config = $this->processConfiguration([
            'maker' => [
                'root_namespace' => 'Custom\\Name\\Space',
                'generate_final_classes' => false,
                'generate_final_entities' => true,
            ],
        ]);

        $this->assertSame('Custom\\Name\\Space', $config['root_namespace']);
        $this->assertFalse($config['generate_final_classes']);
        $this->assertTrue($config['generate_final_entities']);
    }

    public function testCustomNamespaces()
    {
        $config = $this->processConfiguration([
            'maker' => [
                'namespaces' => [
                    'entity' => 'Domain\\Entity',
                    'controller' => 'Application\\Controller',
                    'repository' => 'Infrastructure\\Repository',
                ],
            ],
        ]);

        $this->assertSame('Domain\\Entity', $config['namespaces']['entity']);
        $this->assertSame('Application\\Controller', $config['namespaces']['controller']);
        $this->assertSame('Infrastructure\\Repository', $config['namespaces']['repository']);
        $this->assertArrayNotHasKey('command', $config['namespaces']);
        $this->assertArrayNotHasKey('form', $config['namespaces']);
    }

    public function testInvalidRootNamespaceWithReservedKeyword()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"Class" is a reserved keyword');

        $this->processConfiguration([
            'maker' => [
                'root_namespace' => 'App\\Class',
            ],
        ]);
    }

    /**
     * Processes the configuration using the MakerBundle's configure method.
     *
     * @param array<string, mixed> $configs
     *
     * @return array<string, mixed>
     */
    private function processConfiguration(array $configs): array
    {
        $bundle = new MakerBundle();
        $treeBuilder = new TreeBuilder('maker');
        $definitionConfigurator = new DefinitionConfigurator(
            $treeBuilder,
            $this->createStub(DefinitionFileLoader::class),
            __DIR__,
            __FILE__
        );

        $bundle->configure($definitionConfigurator);

        $processor = new Processor();

        return $processor->process($treeBuilder->buildTree(), $configs);
    }
}
