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

use Symfony\Bundle\MakerBundle\Maker\MakeValidator;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeValidatorTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeValidator::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_makes_validator' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // validator name
                        'FooBar',
                    ]
                );

                // Validator
                $expectedVoterPath = \dirname(__DIR__).'/fixtures/make-validator/expected/FooBarValidator.php';
                $generatedVoter = $runner->getPath('src/Validator/FooBarValidator.php');

                self::assertSame(file_get_contents($expectedVoterPath), file_get_contents($generatedVoter));

                // Constraint
                $expectedVoterPath = \dirname(__DIR__).'/fixtures/make-validator/expected/FooBar.php';
                $generatedVoter = $runner->getPath('src/Validator/FooBar.php');

                self::assertSame(file_get_contents($expectedVoterPath), file_get_contents($generatedVoter));
            }),
        ];
    }
}
