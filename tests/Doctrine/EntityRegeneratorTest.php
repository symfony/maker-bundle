<?php

namespace Symfony\Bundle\MakerBundle\Tests\Doctrine;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MakerBundle\Doctrine\EntityRegenerator;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
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
    public function testRegenerateEntities(string $expectedDirName, bool $overwrite)
    {
        $kernel = new TestEntityRegeneratorKernel('dev', true);
        $this->doTestRegeneration(
            __DIR__.'/fixtures/source_project',
            $kernel,
            'Symfony\Bundle\MakerBundle\Tests\Doctrine\fixtures\source_project\src\Entity',
            $expectedDirName,
            $overwrite
        );
    }

    public function getRegenerateEntitiesTests()
    {
        yield 'regnerate_no_overwrite' => [
            'expected_no_overwrite',
            false
        ];

        yield 'regenerate_overwrite' => [
            'expected_overwrite',
            true
        ];
    }

    public function testXmlRegeneration()
    {
        $kernel = new TestXmlEntityRegeneratorKernel('dev', true);
        $this->doTestRegeneration(
            __DIR__.'/fixtures/xml_source_project',
            $kernel,
            'Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity',
            'expected_xml',
            false
        );
    }

    private function doTestRegeneration(string $sourceDir, Kernel $kernel, string $namespace, string $expectedDirName, bool $overwrite)
    {
        $fs = new Filesystem();
        $tmpDir = __DIR__.'/../tmp/current_project';
        $fs->remove($tmpDir);
        $fs->mirror($sourceDir, $tmpDir);

        $kernel->boot();
        $container = $kernel->getContainer();

        $autoloaderUtil = $this->createMock(AutoloaderUtil::class);
        $autoloaderUtil->expects($this->any())
            ->method('getPathForFutureClass')
            ->willReturnCallback(function($className) use ($tmpDir) {
                $shortClassName = str_replace('Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\\', '', $className);

                // strip the App\, change \ to / and add .php
                return $tmpDir.'/src/'.str_replace('\\', '/', $shortClassName).'.php';
            });

        $fileManager = new FileManager($fs, $autoloaderUtil, $tmpDir);
        $regenerator = new EntityRegenerator(
            $container->get('doctrine'),
            $fileManager,
            new Generator($fileManager, 'App\\'),
            // project root: just used to fetch the autoloader
            // and in this case, we want to use *our* autoloader
            __DIR__.'/../../',
            $overwrite
        );

        $regenerator->regenerateEntities($namespace);

        $expectedDir = sprintf(__DIR__.'/fixtures/%s/src', $expectedDirName);
        $finder = (new Finder())->in($expectedDir)->files();

        foreach ($finder as $file) {

            /** @var SplFileInfo $file */
            $expectedContents = file_get_contents($file->getPathname());

            $actualRelativePath = ltrim(str_replace($expectedDir, '', $file->getPathname()), '/');
            $actualPath = sprintf('%s/src/%s', $tmpDir, $actualRelativePath);
            $this->assertFileExists($actualPath, sprintf('Could not find expected file src/%s', $actualRelativePath));
            $actualContents = file_get_contents($actualPath);

            $this->assertEquals($expectedContents, $actualContents, sprintf('File "%s" does not match: %s', $file->getFilename(), $actualContents));
        }
    }
}

class TestEntityRegeneratorKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return array(
            new FrameworkBundle(),
            new DoctrineBundle(),
        );
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->setParameter('kernel.secret', 123);
        $c->prependExtensionConfig('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'url' => 'sqlite:///fake',
            ],
            'orm' => [
                'mappings' => [
                    'EntityRegenerator' => [
                        'is_bundle' => false,
                        'type' => 'annotation',
                        'dir' => '%kernel.root_dir%/src/Entity',
                        'prefix' => 'Symfony\Bundle\MakerBundle\Tests\Doctrine\fixtures\source_project\src\Entity',
                        'alias' => 'EntityRegeneratorApp',
                    ]
                ]
            ]
        ]);
    }

    public function getRootDir()
    {
        return __DIR__.'/../tmp/current_project';
    }
}

class TestXmlEntityRegeneratorKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return array(
            new FrameworkBundle(),
            new DoctrineBundle(),
        );
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->setParameter('kernel.secret', 123);
        $c->prependExtensionConfig('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'url' => 'sqlite:///fake',
            ],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'mappings' => [
                    'EntityRegenerator' => [
                        'is_bundle' => false,
                        'type' => 'xml',
                        'dir' => '%kernel.root_dir%/config/doctrine',
                        'prefix' => 'Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity',
                        'alias' => 'EntityRegeneratorApp',
                    ]
                ]
            ]
        ]);
    }

    public function getRootDir()
    {
        return __DIR__.'/../tmp/current_project';
    }
}