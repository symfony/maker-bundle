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

use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineODMHelper;
use Symfony\Bundle\MakerBundle\Doctrine\DocumentClassGenerator;
use Symfony\Bundle\MakerBundle\Doctrine\DocumentRegenerator;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class DocumentRegeneratorTest extends TestCase
{
    /**
     * @dataProvider getRegenerateDocumentsTests
     */
    public function testRegenerateDocuments(string $expectedDirName, bool $overwrite): void
    {
        $kernel = new TestDocumentRegeneratorKernel('dev', true);
        $this->doTestRegeneration(
            __DIR__.'/fixtures/source_project',
            $kernel,
            'Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Document',
            $expectedDirName,
            $overwrite,
            'current_project'
        );
    }

    public function getRegenerateDocumentsTests(): \Generator
    {
        yield 'regenerate_no_overwrite' => [
            'expected_no_overwrite_odm',
            false,
        ];

        yield 'regenerate_overwrite' => [
            'expected_overwrite_odm',
            true,
        ];
    }

    public function testXmlRegeneration(): void
    {
        $kernel = new TestXmlDocumentRegeneratorKernel('dev', true);
        $this->doTestRegeneration(
            __DIR__.'/fixtures/xml_source_project',
            $kernel,
            'Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Document',
            'expected_xml_odm',
            false,
            'current_project_xml'
        );
    }

    private function doTestRegeneration(string $sourceDir, Kernel $kernel, string $namespace, string $expectedDirName, bool $overwrite, string $targetDirName): void
    {
        $fs = new Filesystem();
        $tmpDir = __DIR__.'/../tmp/'.$targetDirName;
        $fs->remove($tmpDir);

        // if traits (Timestampable, Teamable) gets copied into new project, tests will fail because of double exclusion
        $fs->mirror($sourceDir, $tmpDir, $this->createAllDocumentsButTraitsIterator($sourceDir));

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
        $doctrineHelper = new DoctrineODMHelper('App\\Document', $container->get('doctrine_mongodb'));
        $generator = new Generator($fileManager, 'App\\');
        $documentClassGenerator = new DocumentClassGenerator($generator, $doctrineHelper);
        $regenerator = new DocumentRegenerator(
            $doctrineHelper,
            $fileManager,
            $generator,
            $documentClassGenerator,
            $overwrite
        );

        $regenerator->regenerateDocuments($namespace);

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

    private function createAllDocumentsButTraitsIterator(string $sourceDir): \Iterator
    {
        $directoryIterator = new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS);
        $filter = new AllDocumentsButTraitsIterator($directoryIterator);

        return new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::SELF_FIRST);
    }
}

class TestDocumentRegeneratorKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new DoctrineMongoDBBundle()
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
        ]);

        $c->prependExtensionConfig('doctrine_mongodb', [
            'connections' => [
                'default' => [
                    'server' => 'mongodb://localhost:27017',
                    'options' => [],
                    ]
            ],
            'document_managers' => [
                'default' => [
                    'mappings' => [
                        'DocumentRegenerator' => [
                            'is_bundle' => false,
                            'dir' => '%kernel.project_dir%/src/Document',
                            'prefix' => 'Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Document',
                            'alias' => 'DocumentRegeneratorApp',
                        ],
                    ],
                ]
            ],
        ]);
    }

    public function getProjectDir(): string
    {
        return __DIR__.'/../tmp/current_project';
    }
}

class TestXmlDocumentRegeneratorKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new DoctrineMongoDBBundle()
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
        ]);


        $c->prependExtensionConfig('doctrine_mongodb', [
            'auto_generate_proxy_classes' => true,
            'auto_generate_hydrator_classes' => true,
            'auto_generate_persistent_collection_classes' => true,
            'connections' => [
                'default' => [
                    'server' => 'mongodb://localhost:27017',
                    'options' => [],
                ]
            ],
            'document_managers' => [
                'default' => [
                    'mappings' => [
                        'DocumentRegenerator' => [
                            'is_bundle' => false,
                            'type' => 'xml',
                            'dir' => '%kernel.project_dir%/config/doctrine_mongodb',
                            'prefix' => 'Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Document',
                            'alias' => 'DocumentRegeneratorApp',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function getProjectDir(): string
    {
        return __DIR__.'/../tmp/current_project_xml';
    }
}

class AllDocumentsButTraitsIterator extends \RecursiveFilterIterator
{
    public function accept(): bool
    {
        return !\in_array($this->current()->getFilename(), []) && !stristr($this->current()->getFilename(), 'entity');
    }
}
