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

use Symfony\Bundle\MakerBundle\Maker\MakeDataPersister;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeDataPersisterTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'api_data_persister' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDataPersister::class),
            [
                // data persister name
                'CustomDataPersister',
                ' ',
            ])->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/DataPersister/CustomDataPersister.php');
            }),
        ];
        yield 'entity_with_doctrine_persister' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDataPersister::class),
            [
                'ArticleDataPersister',
                'Article',
                'yes',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDataPersister')
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/DataPersister/ArticleDataPersister.php');
            }),
        ];
        yield 'entity_without_doctrine_persister' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDataPersister::class),
            [
                'ArticleBlogDataPersister',
                'Article',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDataPersister')
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/DataPersister/ArticleBlogDataPersister.php');
            }),
        ];
        yield 'model_class_persister' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDataPersister::class),
            [
                'BookDataPersister',
                'Book',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDataPersister')
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/DataPersister/BookDataPersister.php');
            }),
        ];
    }
}
