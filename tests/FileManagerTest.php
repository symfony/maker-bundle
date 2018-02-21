<?php

namespace Symfony\Bundle\MakerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class FileManagerTest extends MakerTestCase
{
    /**
     * @dataProvider getRelativizePathTests
     */
    public function testRelativizePath(string $rootDir, string $absolutePath, string $expectedPath)
    {
        $fileManager = new FileManager(new Filesystem(), $rootDir);

        $this->assertSame($expectedPath, $fileManager->relativizePath($absolutePath));
    }

    public function getRelativizePathTests()
    {
        yield [
            '/home/project',
            '/some/other/path',
            '/some/other/path',
        ];

        yield [
            '/home/project',
            '/home/project/foo/bar',
            'foo/bar',
        ];

        yield [
            '/home/project',
            '/home/project//foo/./bar',
            'foo/bar',
        ];

        yield 'relative_dot_path' => [
            '/home/project',
            '/home/project/foo/bar/../../src/Baz.php',
            'src/Baz.php',
        ];

        yield 'windows_path' => [
            'D:\path\to\project',
            'D:\path\to\project\vendor\composer/../../src/Controller/TestController.php',
            'src/Controller/TestController.php',
        ];
    }

    public function testGetPathForFutureClass()
    {
        $composerJson = [
            'autoload' => [
                'psr-4' => [
                    'Also\\In\\Src\\' => 'src/SubDir',
                    'App\\' => 'src/',
                    'Other\\Namespace\\' => 'lib',
                    '' => 'fallback_dir',
                ],
                'psr-0' => [
                    'Psr0\\Package' => 'lib/other',
                ],
            ],
        ];

        $fs = new Filesystem();
        if (!file_exists(self::$currentRootDir)) {
            $fs->mkdir(self::$currentRootDir);
        }

        $fs->remove(self::$currentRootDir.'/vendor');
        file_put_contents(
            self::$currentRootDir.'/composer.json',
            json_encode($composerJson, JSON_PRETTY_PRINT)
        );
        $process = new Process('composer dump-autoload', self::$currentRootDir);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \Exception('Error running composer dump-autoload: '.$process->getErrorOutput());
        }

        $fileManager = new FileManager(new Filesystem(), self::$currentRootDir);
        foreach ($this->getPathForFutureClassTests() as $className => $expectedPath) {
            $this->assertSame($expectedPath, $fileManager->getPathForFutureClass($className), sprintf('class "%s" should have been in path "%s"', $className, $expectedPath));
        }
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

    /**
     * @dataProvider getAbsolutePathTests
     */
    public function testAbsolutizePath(string $rootDir, string $path, string $expectedPath)
    {
        $fileManager = new FileManager(new Filesystem(), $rootDir);
        $this->assertSame($expectedPath, $fileManager->absolutizePath($path));
    }

    public function getAbsolutePathTests()
    {
        yield 'normal_path_change' => [
            '/home/project/',
            'foo/bar',
            '/home/project/foo/bar',
        ];

        yield 'already_absolute_path' => [
            '/home/project/',
            '/foo/bar',
            '/foo/bar',
        ];

        yield 'windows_already_absolute_path' => [
            'D:\path\to\project',
            'D:\foo\bar',
            'D:\foo\bar',
        ];
    }
}
