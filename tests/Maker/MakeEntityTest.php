<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeEntity;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Component\Finder\Finder;

class MakeEntityTest extends MakerTestCase
{
    public function getTestDetails()
    {
        $strings = [
            'string' => 'SearchFilter',
            'string' => 'OrderFilter',
            'string' => 'ExistsFilter',
        ];

        foreach ($strings as $type => $filter) {
            $testName = 'entity_new_api_resource_filters_string_'.$filter;

            yield $testName => [MakerTestDetails::createTest(
                $this->getMakerInstance(MakeEntity::class),
                [
                    // entity class name
                    'User',
                    // add not additional fields
                    'name',
                    $type, // type
                    '1000', // length
                    'y', // nullable
                    $filter,
                    '',
                ])
                ->setArgumentsString('--api-resource')
                ->addExtraDependencies('api')
                ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntity')
                ->configureDatabase()
                ->updateSchemaAfterCommand()
                ->assert(function (string $output, string $directory) {
                    $this->assertFileExists($directory.'/src/Entity/User.php');
                    $content = file_get_contents($directory.'/src/Entity/User.php');

                    $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiResource;', $content);
                    $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiFilter;', $content);
                    $this->assertStringContainsString('@ApiResource', $content);
                }),
            ];
        }

        $textFilters = [
            'text' => 'SearchFilter',
            'text' => 'OrderFilter',
            'text' => 'ExistsFilter',
        ];

        foreach ($textFilters as $type => $filter) {
            $testName = 'entity_new_api_resource_filters_text_'.$filter;

            yield $testName => [MakerTestDetails::createTest(
                $this->getMakerInstance(MakeEntity::class),
                [
                    // entity class name
                    'User',
                    // add not additional fields
                    'name',
                    $type, // type
                    'y', // nullable
                    $filter,
                    '',
                ])
                ->setArgumentsString('--api-resource')
                ->addExtraDependencies('api')
                ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntity')
                ->configureDatabase()
                ->updateSchemaAfterCommand()
                ->assert(function (string $output, string $directory) {
                    $this->assertFileExists($directory.'/src/Entity/User.php');
                    $content = file_get_contents($directory.'/src/Entity/User.php');

                    $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiResource;', $content);
                    $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiFilter;', $content);
                    $this->assertStringContainsString('@ApiResource', $content);
                }),
            ];
        }

        $numericsAndBool = [
            'integer' => 'NumericFilter',
            'smallint' => 'NumericFilter',
            'bigint' => 'NumericFilter',
            'guid' => 'NumericFilter',

            'integer' => 'RangeFilter',
            'smallint' => 'RangeFilter',
            'bigint' => 'RangeFilter',
            'guid' => 'RangeFilter',

            'integer' => 'OrderFilter',
            'smallint' => 'OrderFilter',
            'bigint' => 'OrderFilter',
            'guid' => 'OrderFilter',

            'integer' => 'SearchFilter',
            'smallint' => 'SearchFilter',
            'bigint' => 'SearchFilter',
            'guid' => 'SearchFilter',

            'integer' => 'ExistsFilter',
            'smallint' => 'ExistsFilter',
            'bigint' => 'ExistsFilter',
            'guid' => 'ExistsFilter',

            'boolean' => 'BooleanFilter',
        ];

        foreach ($numericsAndBool as $type => $filter) {
            $testName = 'entity_new_api_resource_filters_numerics_bool_'.$type.'_'.$filter;

            yield $testName => [MakerTestDetails::createTest(
                $this->getMakerInstance(MakeEntity::class),
                [
                    // entity class name
                    'User',
                    // add not additional fields
                    'name',
                    $type, // type
                    'y', // nullable
                    $filter,
                    '',
                ])
                ->setArgumentsString('--api-resource')
                ->addExtraDependencies('api')
                ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntity')
                ->configureDatabase()
                ->updateSchemaAfterCommand()
                ->assert(function (string $output, string $directory) {
                    $this->assertFileExists($directory.'/src/Entity/User.php');
                    $content = file_get_contents($directory.'/src/Entity/User.php');
                    $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiResource;', $content);
                    $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiFilter;', $content);
                    $this->assertStringContainsString('@ApiResource', $content);
                }),
            ];
        }

        $dates = [
            'datetime' => 'DateFilter',
            'datetime_immutable' => 'DateFilter',
            'datetimetz' => 'DateFilter',
            'datetimetz_immutable' => 'DateFilter',
            'date' => 'DateFilter',
            'date_immutable' => 'DateFilter',
            'time' => 'DateFilter',
            'time_immutable' => 'DateFilter',
            'dateinterval' => 'DateFilter',
        ];

        foreach ($dates as $type => $filter) {
            $testName = 'entity_new_api_resource_filters_dates_'.$type.'_'.$filter;

            yield $testName => [MakerTestDetails::createTest(
                $this->getMakerInstance(MakeEntity::class),
                [
                    // entity class name
                    'User',
                    // add not additional fields
                    'createdAt',
                    $type, // type
                    'y', // nullable
                    $filter,
                    '',
                ])
                ->setArgumentsString('--api-resource')
                ->addExtraDependencies('api')
                ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntity')
                ->configureDatabase()
                ->updateSchemaAfterCommand()
                ->assert(function (string $output, string $directory) {
                    $this->assertFileExists($directory.'/src/Entity/User.php');
                    $content = file_get_contents($directory.'/src/Entity/User.php');
                    $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiResource;', $content);
                    $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiFilter;', $content);
                    $this->assertStringContainsString('@ApiResource', $content);
                }),
            ];
        }
    }
}
