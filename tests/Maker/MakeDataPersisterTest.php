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
            ])->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/DataPersister/CustomDataPersister.php');
            }),
        ];
    }
}
