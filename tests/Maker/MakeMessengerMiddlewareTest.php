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

use Symfony\Bundle\MakerBundle\Maker\MakeMessengerMiddleware;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeMessengerMiddlewareTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'messenger_middleware' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeMessengerMiddleware::class),
            [
                // middleware name
                'CustomMiddleware',
            ])->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/Middleware/CustomMiddleware.php');
            }),
        ];
    }
}
