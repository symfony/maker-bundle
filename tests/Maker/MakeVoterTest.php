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

use Symfony\Bundle\MakerBundle\Maker\MakeVoter;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\Yaml\Yaml;

class MakeVoterTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeVoter::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_makes_voter' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // voter class name
                        'FooBar',
                    ]
                );

                $expectedVoterPath = \dirname(__DIR__).'/fixtures/make-voter/expected/FooBarVoter.php';
                $generatedVoter = $runner->getPath('src/Security/Voter/FooBarVoter.php');

                self::assertSame(file_get_contents($expectedVoterPath), file_get_contents($generatedVoter));
            }),
        ];

        yield 'it_makes_voter_not_final' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->writeFile(
                    'config/packages/dev/maker.yaml',
                    Yaml::dump(['when@dev' => ['maker' => ['generate_final_classes' => false]]])
                );

                $runner->runMaker(
                    [
                        // voter class name
                        'FooBar',
                    ]
                );

                $expectedVoterPath = \dirname(__DIR__).'/fixtures/make-voter/expected/not_final_FooBarVoter.php';
                $generatedVoter = $runner->getPath('src/Security/Voter/FooBarVoter.php');

                self::assertSame(file_get_contents($expectedVoterPath), file_get_contents($generatedVoter));
            }),
        ];
    }
}
