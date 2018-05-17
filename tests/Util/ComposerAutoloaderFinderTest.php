<?php

namespace Symfony\Bundle\MakerBundle\Tests\Util;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder;

class ComposerAutoloaderFinderTest extends TestCase
{
    public static $getSplAutoloadFunctions = 'spl_autoload_functions';

    /**
     * @after
     */
    public function resetAutoloadFunction()
    {
        self::$getSplAutoloadFunctions = 'spl_autoload_functions';
    }

    public function testGetClassLoader()
    {
        $loader = (new ComposerAutoloaderFinder())->getClassLoader();

        $this->assertInstanceOf(ClassLoader::class, $loader, 'Wrong ClassLoader found');
    }

    /**
     * @expectedException \Exception
     */
    public function testGetClassLoaderWhenItIsEmpty()
    {
        self::$getSplAutoloadFunctions = function () {
            return [];
        };

        // throws \Exception
        (new ComposerAutoloaderFinder())->getClassLoader();
    }
}

namespace Symfony\Bundle\MakerBundle\Util;

use Symfony\Bundle\MakerBundle\Tests\Util\ComposerAutoloaderFinderTest;

function spl_autoload_functions()
{
    return call_user_func_array(ComposerAutoloaderFinderTest::$getSplAutoloadFunctions, func_get_args());
}
