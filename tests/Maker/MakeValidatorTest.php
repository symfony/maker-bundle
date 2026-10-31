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

    public static function getTestDetails(): \Generator
    {
        yield 'it_makes_validator' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
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

        yield 'it_rejects_invalid_validator_name_non_interactively' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '--no-interaction "Foo Bar"', allowedToFail: true);

                self::assertStringContainsString('The validator class "Validator\Foo Bar" is not a valid class name.', $output);
                self::assertFileDoesNotExist($runner->getPath('src/Validator/FooBarValidator.php'));
                self::assertFileDoesNotExist($runner->getPath('src/Validator/FooBar.php'));
            }),
        ];
    }
}
