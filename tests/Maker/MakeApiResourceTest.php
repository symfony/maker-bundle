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
                'get, post, put, delete, patch', // put, delete, patch not availables
                'get, post, put, delete, patch', // post not available
                'end',

                '',
            ])
            ->addExtraDependencies('api')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeApiResource')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/Entity/Product.php');
                $content = file_get_contents($directory.'/src/Entity/Product.php');

                foreach (['post', 'put', 'delete', 'patch'] as $methodNotAvailable) {
                    $this->assertStringContainsString(sprintf(
                        '! [NOTE] The option "%s" is not available and has been ignored.',
                        $methodNotAvailable
                    ), $output);
                }

                $this->assertStringContainsString('collectionOperations={"get", "post"}', $content);
                $this->assertStringContainsString('itemOperations={"get", "put", "delete", "patch"}', $content);
            }),
        ];

        yield 'entity_new_api_resource_filters_SearchFilter' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeApiResource::class),
            [
                // entity class name
                'Product',

                'end', // configuration of ApiResource()

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
                'end', // configuration of ApiResource()

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
                'end', // configuration of ApiResource()

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

                'end', // configuration of ApiResource()

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
