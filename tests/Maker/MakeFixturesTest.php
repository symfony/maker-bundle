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

use Symfony\Bundle\MakerBundle\Maker\MakeFixtures;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeFixturesTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeFixtures::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_generates_fixtures' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    'FooFixtures',
                ]);

                $this->assertStringContainsString('src/DataFixtures/FooFixtures.php', $output);
            }),
        ];
    }
}
