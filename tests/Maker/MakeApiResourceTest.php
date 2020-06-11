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

use Symfony\Bundle\MakerBundle\Maker\MakeApiResource;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeApiResourceTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'entity_new_api_resource_configuration' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeApiResource::class),
            [
                // entity class name
                'Product',
                // configuration of ApiResource()
                0,
                'get',
                'post',
                'get',
                'put',
                'delete',
                'patch',

                1,
                'book:read, author:read',
                'book:write, author:write',

                2,
                'client_enabled',
                'true',
                'items_per_page',
                30,
                'client_items_per_page',
                30,
                'maximum_items_per_page',
                30,
                'partial',
                'true',
                'client_partial',
                'true',
                '',

                3,
                'jsonld',
                'n/a',
                'jsonapi',
                'jsonhal',
                'yaml',
                'csv',
                'html',
                'xml',
                'json',

                4,
                'custom_argument',
                'custom_value_1',
                125,
                '',
                '',

                5,
                'messenger=true',
                '',

                'next',

                '',
            ])
            ->addExtraDependencies('api')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeApiResource')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/Entity/Product.php');
                $content = file_get_contents($directory.'/src/Entity/Product.php');

                $this->assertStringContainsString('collectionOperations={"get", "post"}', $content);
                $this->assertStringContainsString('itemOperations={"get", "put", "delete", "patch"}', $content);

                $this->assertStringContainsString('normalizationContext={"groups"={"book:read", "author:read"}}', $content);
                $this->assertStringContainsString('denormalizationContext={"groups"={"book:write", "author:write"}}', $content);

                $availableFormats = [
                    'application/ld+json' => 'jsonld',
                    'n/a' => 'n/a',
                    'application/vnd.api+json' => 'jsonapi',
                    'application/hal+json' => 'jsonhal',
                    'application/x-yaml' => 'yaml',
                    'text/csv' => 'csv',
                    'text/html' => 'html',
                    'application/xml' => 'xml',
                    'application/json' => 'json',
                ];

                foreach ($availableFormats as $mimeType => $format) {
                    $this->assertStringContainsString(sprintf('"%s"={"%s"},', $format, $mimeType), $content);
                }

                $paginationOptions = [
                    'pagination_client_enabled' => 'true',
                    'pagination_items_per_page' => 30,
                    'pagination_client_items_per_page' => 30,
                    'maximum_items_per_page' => 30,
                    'pagination_partial' => 'true',
                    'pagination_client_partial' => 'true',
                ];

                foreach ($paginationOptions as $option => $value) {
                    $this->assertStringContainsString(sprintf('"%s"=%s', $option, $value), $content);
                }

                $this->assertStringContainsString('"custom_argument"={"custom_value_1", 125}', $content);

                $this->assertStringContainsString('messenger=true,', $content);
            }),
        ];

        yield 'entity_new_api_resource_filters_SearchFilter' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeApiResource::class),
            [
                // entity class name
                'Product',

                'next', // configuration of ApiResource()

                // add not additional fields
                'nameString',
                'string', // type
                '250', // length
                'y', // nullable
                'SearchFilter',
                'partial',
                '',

                'nameText',
                'text', // type
                'y', // nullable
                'SearchFilter',
                'start',
                '',

                'nameInteger',
                'integer', // type
                'y', // nullable
                'SearchFilter',
                'end', // configuration of ApiResource()
                '',

                'nameExact',
                'integer', // type
                'y', // nullable
                'SearchFilter',
                'exact',
                '',

                // add not additional fields
                'inameString',
                'string', // type
                '250', // length
                'y', // nullable
                'SearchFilter',
                'ipartial',
                '',

                'inameText',
                'text', // type
                'y', // nullable
                'SearchFilter',
                'istart',
                '',

                'inameInteger',
                'integer', // type
                'y', // nullable
                'SearchFilter',
                'iend',
                '',

                'inameExact',
                'integer', // type
                'y', // nullable
                'SearchFilter',
                'iexact',
                '',

                '',
            ])
            ->addExtraDependencies('api')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeApiResourceSearchFilter')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/Entity/Product.php');
                $content = file_get_contents($directory.'/src/Entity/Product.php');

                $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiResource;', $content);
                $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiFilter;', $content);
                $this->assertStringContainsString('use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;', $content);
                $this->assertStringContainsString('@ApiResource', $content);
                $this->assertStringContainsString('@ApiFilter(SearchFilter::class, strategy="partial")', $content);
                $this->assertStringContainsString('@ApiFilter(SearchFilter::class, strategy="start")', $content);
                $this->assertStringContainsString('@ApiFilter(SearchFilter::class, strategy="end")', $content);
                $this->assertStringContainsString('@ApiFilter(SearchFilter::class, strategy="exact")', $content);
                $this->assertStringContainsString('@ApiFilter(SearchFilter::class, strategy="ipartial")', $content);
                $this->assertStringContainsString('@ApiFilter(SearchFilter::class, strategy="istart")', $content);
                $this->assertStringContainsString('@ApiFilter(SearchFilter::class, strategy="iend")', $content);
                $this->assertStringContainsString('@ApiFilter(SearchFilter::class, strategy="iexact")', $content);
            }),
        ];

        yield 'entity_new_api_resource_filters_DateFilter' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeApiResource::class),
            [
                // entity class name
                'Product',
                'next', // configuration of ApiResource()

                // add not additional fields
                'nameDatetime',
                'datetime', // type
                'y', // nullable
                'DateFilter',
                'EXCLUDE_NULL',
                '',

                // add not additional fields
                'nameDate',
                'date', // type
                'y', // nullable
                'DateFilter',
                'INCLUDE_NULL_BEFORE',
                '',

                // add not additional fields
                'nameDatetimea',
                'datetime', // type
                'y', // nullable
                'DateFilter',
                'INCLUDE_NULL_AFTER',
                '',

                // add not additional fields
                'nameDateb',
                'date', // type
                'y', // nullable
                'DateFilter',
                'INCLUDE_NULL_BEFORE_AND_AFTER',
                '',

                '',
            ])
            ->addExtraDependencies('api')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeApiResourceDateFilter')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/Entity/Product.php');
                $content = file_get_contents($directory.'/src/Entity/Product.php');

                $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiResource;', $content);
                $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiFilter;', $content);
                $this->assertStringContainsString('use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;', $content);
                $this->assertStringContainsString('@ApiResource', $content);
                $this->assertStringContainsString('@ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)', $content);
                $this->assertStringContainsString('@ApiFilter(DateFilter::class, strategy=DateFilter::INCLUDE_NULL_BEFORE)', $content);
                $this->assertStringContainsString('@ApiFilter(DateFilter::class, strategy=DateFilter::INCLUDE_NULL_AFTER)', $content);
                $this->assertStringContainsString('@ApiFilter(DateFilter::class, strategy=DateFilter::INCLUDE_NULL_BEFORE_AND_AFTER)', $content);
            }),
        ];

        yield 'entity_new_api_resource_filters_others' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeApiResource::class),
            [
                // entity class name
                'Product',
                'next', // configuration of ApiResource()

                // add not additional fields
                'isAvailableGenericallyInMyCountry',
                'boolean', // type
                'y', // nullable
                'BooleanFilter',
                '',

                // add not additional fields
                'sold',
                'integer', // type
                'y', // nullable
                'NumericFilter',
                '',

                // add not additional fields
                'price',
                'float', // type
                'y', // nullable
                'RangeFilter',
                '',

                // add not additional fields
                'transportFees',
                'text', // type
                'y', // nullable
                'ExistsFilter',
                'OrderFilter',
                '',

                '',
            ])
            ->addExtraDependencies('api')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeApiResourceOtherFilters')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/Entity/Product.php');
                $content = file_get_contents($directory.'/src/Entity/Product.php');

                $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiResource;', $content);
                $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiFilter;', $content);
                $this->assertStringContainsString('use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;', $content);
                $this->assertStringContainsString('use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\NumericFilter;', $content);
                $this->assertStringContainsString('use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;', $content);
                $this->assertStringContainsString('use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;', $content);
                $this->assertStringContainsString('use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;', $content);
                $this->assertStringContainsString('@ApiResource', $content);
                $this->assertStringContainsString('@ApiFilter(BooleanFilter::class)', $content);
                $this->assertStringContainsString('@ApiFilter(NumericFilter::class)', $content);
                $this->assertStringContainsString('@ApiFilter(RangeFilter::class)', $content);
                $this->assertStringContainsString('@ApiFilter(ExistsFilter::class)', $content);
                $this->assertStringContainsString('@ApiFilter(OrderFilter::class)', $content);
            }),
        ];

        // Need to test getting results with elastic search
        yield 'entity_new_api_resource_filters_Elasticsearch' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeApiResource::class),
            [
                // entity class name
                'Product',

                'next', // configuration of ApiResource()

                // add not additional fields
                'nameString',
                'string', // type
                '250', // length
                'y', // nullable
                'MatchFilter',
                '',

                // add not additional fields
                'sold',
                'integer', // type
                'y', // nullable
                'TermFilter',
                '',

                '',
            ])
            ->addExtraDependencies('api')
            ->addExtraDependencies('elasticsearch/elasticsearch')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeApiResourceElasticsearch')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/Entity/Product.php');
                $content = file_get_contents($directory.'/src/Entity/Product.php');

                $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiResource;', $content);
                $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiFilter;', $content);
                $this->assertStringContainsString('use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\MatchFilter;', $content);
                $this->assertStringContainsString('use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\TermFilter;', $content);
                $this->assertStringContainsString('@ApiResource', $content);
                $this->assertStringContainsString('@ApiFilter(MatchFilter::class)', $content);
                $this->assertStringContainsString('@ApiFilter(TermFilter::class)', $content);
            }),
        ];
    }
}
