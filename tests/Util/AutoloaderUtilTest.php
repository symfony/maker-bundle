<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Util;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder;
use Symfony\Component\Filesystem\Filesystem;

class AutoloaderUtilTest extends TestCase
{
    protected static $currentRootDir;

    /**
     * @beforeClass
     */
    public static function setupPaths()
    {
        $path = __DIR__.'/../tmp/current_project';

        $fs = new Filesystem();
        if (!file_exists($path)) {
            $fs->mkdir($path);
        }

        self::$currentRootDir = realpath($path);
    }

    public function testGetPathForFutureClass()
    {
        $autoloaderUtil = new AutoloaderUtil($this->createComposerAutoloaderFinder());

        foreach ($this->getPathForFutureClassTests() as $className => $expectedPath) {
            $this->assertSame(
                str_replace('\\', '/', self::$currentRootDir.'/'.$expectedPath),
                // normalize slashes for Windows comparison
                str_replace('\\', '/', $autoloaderUtil->getPathForFutureClass($className)),
                \sprintf('class "%s" should have been in path "%s"', $className, $expectedPath)
            );
        }
    }

    public function testIsNamespaceConfiguredToAutoload()
    {
        $autoloaderUtil = new AutoloaderUtil($this->createComposerAutoloaderFinder());

        foreach ($this->isNamespaceConfiguredToAutoloadTests() as $namespace => $expected) {
            $configured = $autoloaderUtil->isNamespaceConfiguredToAutoload($namespace);

            if ($expected) {
                $this->assertTrue($configured, \sprintf('namespace "%s" is not found but must be', $namespace));
            } else {
                $this->assertFalse($configured, \sprintf('namespace "%s" is found but must not be', $namespace));
            }
        }
    }

    private function createComposerAutoloaderFinder(?array $composerJsonParams = null): ComposerAutoloaderFinder
    {
        $composerJsonParams = $composerJsonParams ?: [
            'autoload' => [
                'psr-4' => [
                    'Also\\In\\Src\\' => '/src/SubDir',
                    'App\\' => '/src',
                    'Other\\Namespace\\' => '/lib',
                    '' => '/fallback_dir',
                ],
                'psr-0' => [
                    'Psr0\\Package' => '/lib/other',
                ],
            ],
        ];

        $classLoader = new ClassLoader();

        foreach ($composerJsonParams['autoload'] as $psr => $dirs) {
            foreach ($dirs as $prefix => $path) {
                if ('psr-4' === $psr) {
                    $classLoader->addPsr4($prefix, self::$currentRootDir.$path);
                } else {
                    $classLoader->add($prefix, self::$currentRootDir.$path);
                }
            }
        }

        /** @var \PHPUnit_Framework_MockObject_MockObject|ComposerAutoloaderFinder $finder */
        $finder = $this
            ->getMockBuilder(ComposerAutoloaderFinder::class)
            ->setConstructorArgs(['App\\'])
            ->getMock();

        $finder
            ->method('getClassLoader')
            ->willReturn($classLoader);

        return $finder;
    }

    private function getPathForFutureClassTests()
    {
        return [
            'App\Foo' => 'src/Foo.php',
            'App\Entity\Product' => 'src/Entity/Product.php',
            'Totally\Weird' => 'fallback_dir/Totally/Weird.php',
            'Also\In\Src\Some\OtherClass' => 'src/SubDir/Some/OtherClass.php',
            'Other\Namespace\Admin\Foo' => 'lib/Admin/Foo.php',
            'Psr0\Package\Admin\Bar' => 'lib/other/Psr0/Package/Admin/Bar.php',
            'App\Controller\App\MyController' => 'src/Controller/App/MyController.php',
        ];
    }

    private function isNamespaceConfiguredToAutoloadTests()
    {
        return [
            'App' => true,
            'App\\' => true,
            '\\App' => true,
            '\\App\\' => true,
            'App\\Entity' => true,
            'Also\\In\\Src\\Some' => true,
            'Other\\Namespace\\Admin' => true,
            'Psr0\\Package' => true,
            'Psr0\\Package\\Some' => true,
            'Unknown' => false,
        ];
    }
}
