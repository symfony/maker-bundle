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

use Symfony\Bundle\MakerBundle\Maker\MakeConvertPhpServices;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Component\HttpKernel\Kernel;

class MakeConvertPhpServicesTest extends MakerTestCase
{
    public function getTestDetails()
    {
        if (Kernel::VERSION_ID < 50100) {
            $this->markTestSkipped('Test requires Symfony 5.1');

            return;
        }

        yield 'convert_php_services' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeConvertPhpServices::class),
            [
                '', // use default path
                'y',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeConvertPhpServices')
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('created: config/services.php', $output);
                $this->assertStringContainsString('deleted: config/services.yaml', $output);
            }),
        ];
    }
}
