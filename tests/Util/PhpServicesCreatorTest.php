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
    public function testYamlServicesConversion(string $yamlPath, string $phpExpectedSourcePath, bool $shouldCompareContainers)
    {
        $creator = new PhpServicesCreator();
        $this->assertSame(
            file_get_contents($phpExpectedSourcePath),
            $creator->convert(file_get_contents($yamlPath))
        );

        if ($shouldCompareContainers) {
            $this->compareContainers($yamlPath, $phpExpectedSourcePath);
        }
    }

    public function getConfigurationFiles()
    {
        $finder = Finder::create()->files()
            ->in(self::YAML_PHP_CONVERT_FIXTURES_PATH.'/source_yaml');

        foreach ($finder as $key => $file) {
            $phpExpectedRealPath = str_replace(['source_yaml', '.yaml'], ['expected_php', '.php'], $file->getRealPath());

            $shouldCompareContainers = true;
            // this file loads "resources", which will fail as there are no real files
            if ($file->getFilename() === 'services_load_resources.yaml') {
                $shouldCompareContainers = false;
            }

            yield $file->getFilenameWithoutExtension() => [
                $file->getRealPath(),
                $phpExpectedRealPath,
                $shouldCompareContainers,
            ];
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
}
