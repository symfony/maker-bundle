<?php

namespace Symfony\Bundle\MakerBundle\Tests\Util;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
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
        $classLoader = new ClassLoader();
        $composerJson = [
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

        foreach ($composerJson['autoload'] as $psr => $dirs) {
            foreach ($dirs as $prefix => $path) {
                if ($psr == 'psr-4') {
                    $classLoader->addPsr4($prefix, self::$currentRootDir.$path);
                } else {
                    $classLoader->add($prefix, self::$currentRootDir.$path);
                }
            }
        }

        $reflection = new \ReflectionClass(AutoloaderUtil::class);
        $property = $reflection->getProperty('classLoader');
        $property->setAccessible(true);

        $autoloaderUtil = new AutoloaderUtil();

        $property->setValue($autoloaderUtil, $classLoader);

        foreach ($this->getPathForFutureClassTests() as $className => $expectedPath) {
            $this->assertSame(
                str_replace('\\', '/', self::$currentRootDir.'/'.$expectedPath),
                // normalize slashes for Windows comparison
                str_replace('\\', '/', $autoloaderUtil->getPathForFutureClass($className)),
                sprintf('class "%s" should have been in path "%s"', $className, $expectedPath)
            );
        }
    }

    public function testCanFindClassLoader()
    {
        $reflection = new \ReflectionClass(AutoloaderUtil::class);
        $method = $reflection->getMethod('getClassLoader');
        $method->setAccessible(true);
        $autoloaderUtil = new AutoloaderUtil();
        $autoloader = $method->invoke($autoloaderUtil);
        $this->assertInstanceOf(ClassLoader::class, $autoloader, 'Wrong ClassLoader found');
    }

    public function getPathForFutureClassTests()
    {
        return [
            'App\Foo' => 'src/Foo.php',
            'App\Entity\Product' => 'src/Entity/Product.php',
            'Totally\Weird' => 'fallback_dir/Totally/Weird.php',
            'Also\In\Src\Some\OtherClass' => 'src/SubDir/Some/OtherClass.php',
            'Other\Namespace\Admin\Foo' => 'lib/Admin/Foo.php',
            'Psr0\Package\Admin\Bar' => 'lib/other/Psr0/Package/Admin/Bar.php'
        ];
    }
}
