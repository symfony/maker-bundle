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

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Util\PhpServicesCreator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Finder\Finder;

// For testing parameters conversion
\define('_MAKER_TEST_GLOBAL_CONSTANT', 'value');

class PhpServicesCreatorTest extends TestCase
{
    // For testing parameters conversion
    const CONSTANT_NAME = 1;

    const YAML_PHP_CONVERT_FIXTURES_PATH = __DIR__.'/yaml_php_convert_fixtures';

    /**
     * @dataProvider getConfigurationFiles
     */
    public function testYamlServicesConversion(string $yamlSource, string $phpExpectedSource, string $filename)
    {
        $creator = new PhpServicesCreator();
        $this->assertSame($phpExpectedSource, $creator->convert($yamlSource));
        /*
         * test if default comments and configuration in services_load_resources.yaml are successfully converted in php but
         * resources "app\", "App\Controller" can't be loaded in this context, then services_load_resources.yaml and
         * services_load_resources.php building are tested with other fake resources by the testFixturesLoad() method above.
         */
        if ('services_load_resources' !== $filename) {
            list($tmpYamlFilename, $tmpPhpFilename) = $this->createTemporaryFiles($yamlSource, $phpExpectedSource);
            $this->compareContainers($tmpYamlFilename, $tmpPhpFilename);
            $this->removeTemporaryFiles($tmpYamlFilename, $tmpPhpFilename);
        }
    }

    public function getConfigurationFiles()
    {
        $finder = Finder::create()->files()
            ->in(self::YAML_PHP_CONVERT_FIXTURES_PATH.'/source_yaml');

        foreach ($finder as $key => $file) {
            $filename = $file->getFilenameWithoutExtension();
            $phpExpectedRealPath = str_replace(['source_yaml', '.yaml'], ['expected_php', '.php'], $file->getRealPath());

            yield $filename => [
                $file->getContents(),
                file_get_contents($phpExpectedRealPath),
                $filename,
            ];
        }
    }

    public function testParameters()
    {
        $source = file_get_contents(self::YAML_PHP_CONVERT_FIXTURES_PATH.'/source_yaml/parameters.yaml');
        $expectedSource = file_get_contents(self::YAML_PHP_CONVERT_FIXTURES_PATH.'/expected_php/parameters.php');

        $creator = new PhpServicesCreator();

        $this->assertSame($expectedSource, $creator->convert($source));
    }

    /*
     * Not the same configuration, only test if they're built the same way.
     */
    public function testFixturesLoad()
    {
        $finder = Finder::create()->files()->name('*.yaml')->notName('somefile.*')
            ->in(self::YAML_PHP_CONVERT_FIXTURES_PATH.'/fixtures_load');

        foreach ($finder as $key => $file) {
            $yamlRealPath = $file->getRealPath();
            $phpExpectedRealPath = str_replace('.yaml', '.php', $file->getRealPath());

            $creator = new PhpServicesCreator();
            $yamlConvert = str_replace('.yaml', '.php', $creator->convert($file->getContents()));
            $this->assertSame(file_get_contents($phpExpectedRealPath), $yamlConvert);

            $this->compareContainers($yamlRealPath, $phpExpectedRealPath);
        }
    }

    public function testCasesWhenContainerIsBuiltDifferently()
    {
        $yamlRealPath = self::YAML_PHP_CONVERT_FIXTURES_PATH.'/container_built_differently/reference.yaml';
        $phpExpectedRealPath = self::YAML_PHP_CONVERT_FIXTURES_PATH.'/container_built_differently/reference.php';

        $source = file_get_contents($yamlRealPath);
        $expectedSource = file_get_contents($phpExpectedRealPath);

        $creator = new PhpServicesCreator();

        $this->assertSame($expectedSource, $creator->convert($source));

        $this->compareContainers($yamlRealPath, $phpExpectedRealPath, false);
    }

    private function compareContainers(string $yamlRealPath, string $phpRealPath, bool $shouldCompareDefinitions = true)
    {
        $yamlContainerBuilder = new ContainerBuilder();
        $yamlLoader = new YamlFileLoader($yamlContainerBuilder, new FileLocator());
        $yamlLoader->load($yamlRealPath);

        $phpContainerBuilder = new ContainerBuilder();
        $phpLoader = new PhpFileLoader($phpContainerBuilder, new FileLocator());
        $phpLoader->load($phpRealPath);

        if ($shouldCompareDefinitions) {
            $this->assertTrue($yamlContainerBuilder->getDefinitions() == $phpContainerBuilder->getDefinitions());
            $this->assertTrue($yamlContainerBuilder->getServiceIds() == $phpContainerBuilder->getServiceIds());
        }

        $this->assertTrue($yamlContainerBuilder->getParameterBag() == $phpContainerBuilder->getParameterBag());
        $this->assertTrue($yamlContainerBuilder->getAliases() == $phpContainerBuilder->getAliases());
    }

    private function createTemporaryFiles(string $yamlSource, string $phpExpectedSource)
    {
        $tmpYamlFilename = tempnam(sys_get_temp_dir(), 'maker_');
        $tmpPhpFilename = tempnam(sys_get_temp_dir(), 'maker_');
        file_put_contents($tmpYamlFilename, $yamlSource);
        file_put_contents($tmpPhpFilename, $phpExpectedSource);

        return [$tmpYamlFilename, $tmpPhpFilename];
    }

    private function removeTemporaryFiles(string $tmpYamlFilename, string $tmpPhpFilename)
    {
        unlink($tmpYamlFilename);
        unlink($tmpPhpFilename);
    }
}
