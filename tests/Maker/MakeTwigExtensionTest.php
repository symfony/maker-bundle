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

use Symfony\Bundle\MakerBundle\Maker\MakeTwigExtension;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeTwigExtensionTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeTwigExtension::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_makes_twig_extension' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // extension class name
                        'FooBar',
                    ]
                );
            }),
        ];
    }
}
