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

use Symfony\Bundle\MakerBundle\Maker\MakeFormExtension;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class MakeFormExtensionTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeFormExtension::class;
    }

    public function getTestDetails()
    {
        yield 'it_makes_form_extension' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // form extension name
                        'Media',
                        DateType::class,
                    ]
                );

                $data = file_get_contents($runner->getPath('src/Form/Extension/MediaTypeExtension.php'));
                $this->assertStringContainsString(sprintf('use %s;', DateType::class), $data);
                $this->assertStringContainsString('DateType::class', $data);
            }),
        ];
    }
}
