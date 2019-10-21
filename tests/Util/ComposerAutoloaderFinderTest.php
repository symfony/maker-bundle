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
use Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder;

class ComposerAutoloaderFinderTest extends TestCase
{
    public static $getSplAutoloadFunctions = 'spl_autoload_functions';

    private static $rootNamespace = 'Fake\\It\\Till\\You\\Make\\It\\';

    /**
     * @after
     */
    public function resetAutoloadFunction()
    {
        self::$getSplAutoloadFunctions = 'spl_autoload_functions';
    }

    public function providerNamespaces(): \Generator
    {
        yield 'Configured PSR-0' => [rtrim(static::$rootNamespace, '\\'), null];
        yield 'Configured PSR-4' => [null, static::$rootNamespace];
        yield 'Fallback default' => [null, 'Symfony\\Bundle\\MakerBundle\\'];
    }

    /**
     * @dataProvider providerNamespaces
     */
    public function testGetClassLoader($psr0, $psr4)
    {
        $this->setupAutoloadFunctions($psr0, $psr4);
        $loader = (new ComposerAutoloaderFinder(static::$rootNamespace))->getClassLoader();

        $this->assertInstanceOf(ClassLoader::class, $loader, 'Wrong ClassLoader found');
    }

    public function testGetClassLoaderWhenItIsEmpty()
    {
        $this->expectException(\Exception::class);

        self::$getSplAutoloadFunctions = function () {
            return [];
        };

        // throws \Exception
        (new ComposerAutoloaderFinder(static::$rootNamespace))->getClassLoader();
    }

    /**
     * @param string|null $psr0
     * @param string|null $psr4
     */
    private function setupAutoloadFunctions($psr0, $psr4)
    {
        self::$getSplAutoloadFunctions = function () use ($psr0, $psr4) {
            $loader = new ClassLoader();
            if ($psr0) {
                $loader->add($psr0, __DIR__);
            }
            if ($psr4) {
                $loader->addPsr4($psr4, __DIR__);
            }

            return [[$loader, 'loadClass']];
        };
    }
}

namespace Symfony\Bundle\MakerBundle\Util;

use Symfony\Bundle\MakerBundle\Tests\Util\ComposerAutoloaderFinderTest;

function spl_autoload_functions()
{
    return \call_user_func_array(ComposerAutoloaderFinderTest::$getSplAutoloadFunctions, \func_get_args());
}
