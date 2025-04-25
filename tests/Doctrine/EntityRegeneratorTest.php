<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Doctrine;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Persistence\Reflection\RuntimeReflectionProperty;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Doctrine\EntityClassGenerator;
use Symfony\Bundle\MakerBundle\Doctrine\EntityRegenerator;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
use Symfony\Bundle\MakerBundle\Util\TemplateComponentGenerator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class EntityRegeneratorTest extends TestCase
{
    /**
     * @dataProvider getRegenerateEntitiesTests
     */
    public function testRegenerateEntities(string $expectedDirName, bool $overwrite): void
    {
        $kernel = new TestEntityRegeneratorKernel('dev', true);
        $this->doTestRegeneration(
            __DIR__.'/fixtures/source_project',
            $kernel,
            'Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity',
            $expectedDirName,
            $overwrite,
            'current_project'
        );
    }

    public function getRegenerateEntitiesTests(): \Generator
    {
        yield 'regenerate_no_overwrite' => [
            'expected_no_overwrite',
            false,
        ];

        yield 'regenerate_overwrite' => [
            'expected_overwrite',
            true,
        ];
    }

    private function doTestRegeneration(string $sourceDir, Kernel $kernel, string $namespace, string $expectedDirName, bool $overwrite, string $targetDirName): void
    {
        $fs = new Filesystem();
        $tmpDir = __DIR__.'/../tmp/'.$targetDirName;
        $fs->remove($tmpDir);

        // if traits (Timestampable, Teamable) gets copied into new project, tests will fail because of double exclusion
        $fs->mirror($sourceDir, $tmpDir, $this->createAllButTraitsIterator($sourceDir));

        $kernel->boot();
        $container = $kernel->getContainer();

        $autoloaderUtil = $this->createMock(AutoloaderUtil::class);
        $autoloaderUtil->expects($this->any())
            ->method('getPathForFutureClass')
            ->willReturnCallback(function ($className) use ($tmpDir, $targetDirName) {
                $shortClassName = str_replace('Symfony\Bundle\MakerBundle\Tests\tmp\\'.$targetDirName.'\src\\', '', $className);

                // strip the App\, change \ to / and add .php
                return $tmpDir.'/src/'.str_replace('\\', '/', $shortClassName).'.php';
            });

        $fileManager = new FileManager($fs, $autoloaderUtil, new MakerFileLinkFormatter(null), $tmpDir);
        $doctrineHelper = new DoctrineHelper('App\\Entity', $container->get('doctrine'));
        $templateComponentGenerator = new TemplateComponentGenerator(false, false, 'App');
        $generator = new Generator(fileManager: $fileManager, namespacePrefix: 'App\\', templateComponentGenerator: $templateComponentGenerator);
        $entityClassGenerator = new EntityClassGenerator($generator, $doctrineHelper);
        $regenerator = new EntityRegenerator(
            $doctrineHelper,
            $fileManager,
            $generator,
            $entityClassGenerator,
            $overwrite
        );

        $regenerator->regenerateEntities($namespace);

        $expectedDir = \sprintf(__DIR__.'/fixtures/%s/src', $expectedDirName);
        $finder = (new Finder())->in($expectedDir)->files();

        foreach ($finder as $file) {
            /** @var SplFileInfo $file */
            $expectedContents = file_get_contents($file->getPathname());

            $actualRelativePath = ltrim(str_replace($expectedDir, '', $file->getPathname()), '/');
            $actualPath = \sprintf('%s/src/%s', $tmpDir, $actualRelativePath);
            $this->assertFileExists($actualPath, \sprintf('Could not find expected file src/%s', $actualRelativePath));
            $actualContents = file_get_contents($actualPath);

            $this->assertEquals($expectedContents, $actualContents, \sprintf('File "%s" does not match: %s', $file->getFilename(), $actualContents));
        }
    }

    private function createAllButTraitsIterator(string $sourceDir): \Iterator
    {
        $directoryIterator = new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS);
        $filter = new AllButTraitsIterator($directoryIterator);

        return new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::SELF_FIRST);
    }
}

class TestEntityRegeneratorKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
        ];
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
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

        $dbal = [
            'driver' => 'pdo_sqlite',
            'url' => 'sqlite:///fake',
        ];

        $orm = [
            'mappings' => [
                'EntityRegenerator' => [
                    'is_bundle' => false,
                    'dir' => '%kernel.project_dir%/src/Entity',
                    'prefix' => 'Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity',
                    'alias' => 'EntityRegeneratorApp',
                    'type' => 'attribute',
                ],
            ],
            'controller_resolver' => [
                'auto_mapping' => false,
            ],
        ];

        /* @legacy Remove conditional when doctrine/persistence <3.1 are no longer supported. */
        if (class_exists(RuntimeReflectionProperty::class)) {
            $orm['enable_lazy_ghost_objects'] = true;
        }

        $c->prependExtensionConfig('doctrine', [
            'dbal' => $dbal,
            'orm' => $orm,
        ]);
    }

    public function getProjectDir(): string
    {
        return __DIR__.'/../tmp/current_project';
    }
}

class TestXmlEntityRegeneratorKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
        ];
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
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

        $dbal = [
            'driver' => 'pdo_sqlite',
            'url' => 'sqlite:///fake',
        ];

        $orm = [
            'auto_generate_proxy_classes' => true,
            'mappings' => [
                'EntityRegenerator' => [
                    'is_bundle' => false,
                    'type' => 'xml',
                    'dir' => '%kernel.project_dir%/config/doctrine',
                    'prefix' => 'Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Entity',
                    'alias' => 'EntityRegeneratorApp',
                ],
            ],
            'controller_resolver' => [
                'auto_mapping' => false,
            ],
        ];

        /* @legacy Remove conditional when doctrine/persistence <3.1 are no longer supported. */
        if (class_exists(RuntimeReflectionProperty::class)) {
            $orm['enable_lazy_ghost_objects'] = true;
        }

        $c->prependExtensionConfig('doctrine', [
            'dbal' => $dbal,
            'orm' => $orm,
        ]);
    }

    public function getProjectDir(): string
    {
        return __DIR__.'/../tmp/current_project_xml';
    }
}

class AllButTraitsIterator extends \RecursiveFilterIterator
{
    public function accept(): bool
    {
        return !\in_array($this->current()->getFilename(), []);
    }
}
