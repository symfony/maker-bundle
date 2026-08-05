<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Test;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MakerBundle\DependencyInjection\CompilerPass\MakeCommandRegistrationPass;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Frozen: kept for third-party bundles that test their own makers with it.
 * MakerBundle's own functional suite uses the internal tests/Harness engine
 * instead. Bug fixes are welcome, new features will not be added.
 */
class MakerTestKernel extends Kernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    private string $testRootDir;

    public function __construct(string $environment, bool $debug)
    {
        $this->testRootDir = sys_get_temp_dir().'/'.uniqid('sf_maker_', true);

        parent::__construct($environment, $debug);
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new MakerBundle(),
        ];
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
    }

    protected function configureRouting(RoutingConfigurator $routes): void
    {
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('framework', [
            'secret' => 123,
            'router' => [
                'utf8' => true,
            ],
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => [
                'log' => true,
            ],
        ]);
    }

    public function getProjectDir(): string
    {
        return $this->getRootDir();
    }

    public function getRootDir(): string
    {
        return $this->testRootDir;
    }

    public function process(ContainerBuilder $container): void
    {
        // Add a service locator to find makers by class name
        $makers = [];
        foreach ($container->findTaggedServiceIds(MakeCommandRegistrationPass::MAKER_TAG) as $id => $tags) {
            $makers[$container->getDefinition($id)->getClass()] = new Reference($id);
        }

        $container->register('maker_locator_for_tests', ServiceLocator::class)
            ->setPublic(true)
            ->addArgument($makers)
            ->addTag('container.service_locator');
    }
}
