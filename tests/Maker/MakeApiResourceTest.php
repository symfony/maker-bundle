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
        yield 'entity_new_api_resource_filters_SearchFilter' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeApiResource::class),
            [
                // entity class name
                'Product',
                // add not additional fields
                'nameString',
                'string', // type
                '250', // length
                'y', // nullable
                'SearchFilter',
                'partial',

                'nameText',
                'text', // type
                'y', // nullable
                'SearchFilter',
                'start',

                'nameInteger',
                'integer', // type
                'y', // nullable
                'SearchFilter',
                'end',

                'nameExact',
                'integer', // type
                'y', // nullable
                'SearchFilter',
                'exact',

                // add not additional fields
                'inameString',
                'string', // type
                '250', // length
                'y', // nullable
                'SearchFilter',
                'ipartial',

                'inameText',
                'text', // type
                'y', // nullable
                'SearchFilter',
                'istart',

                'inameInteger',
                'integer', // type
                'y', // nullable
                'SearchFilter',
                'iend',

                'inameExact',
                'integer', // type
                'y', // nullable
                'SearchFilter',
                'iexact',
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

                // add not additional fields
                'nameDatetime',
                'datetime', // type
                'y', // nullable
                'DateFilter',
                'EXCLUDE_NULL',

                // add not additional fields
                'nameDate',
                'date', // type
                'y', // nullable
                'DateFilter',
                'INCLUDE_NULL_BEFORE',

                // add not additional fields
                'nameDatetimea',
                'datetime', // type
                'y', // nullable
                'DateFilter',
                'INCLUDE_NULL_AFTER',

                // add not additional fields
                'nameDateb',
                'date', // type
                'y', // nullable
                'DateFilter',
                'INCLUDE_NULL_BEFORE_AND_AFTER',

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
    }
}
