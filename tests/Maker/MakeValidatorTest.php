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
use Symfony\Bundle\MakerBundle\Validator\TargetEnum;

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

        yield 'it_makes_validator_constraint_with_target' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $targets = self::getTargets();

                foreach ($targets as $target => $className) {
                    $runner->runMaker(
                        [
                            // Validator name.
                            $className,
                        ],
                        "--target=$target"
                    );

                    $expectedPath = \dirname(__DIR__)."/fixtures/make-validator/expected/$className.php";
                    $generatedPath = $runner->getPath("src/Validator/$className.php");

                    self::assertSame(file_get_contents($expectedPath), file_get_contents($generatedPath));
                }
            }),
        ];

        yield 'it_displays_invalid_target_error_then_asks_valid_target' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker(
                    [
                        // Validator name.
                        'WithTargetDefault',
                        // Target type.
                        '',
                    ],
                    '--target=invalid'
                );

                self::assertStringContainsString('[ERROR] Invalid target "invalid".', $output);
                self::assertStringContainsString('Target type [property]:', $output);
                self::assertStringContainsString('[0] property', $output);
                self::assertStringContainsString('[1] method', $output);
                self::assertStringContainsString('[2] class', $output);
            }),
        ];

        yield 'it_makes_validator_constraint_with_invalid_target_and_default_choice' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $className = 'WithTargetDefault';

                $runner->runMaker(
                    [
                        // Validator name.
                        $className,
                        // Target type.
                        '',
                    ],
                    '--target=invalid'
                );

                $expectedPath = \dirname(__DIR__)."/fixtures/make-validator/expected/$className.php";
                $generatedPath = $runner->getPath("src/Validator/$className.php");

                self::assertSame(file_get_contents($expectedPath), file_get_contents($generatedPath));
            }),
        ];

        yield 'it_makes_validator_constraint_with_invalid_target_and_choice_key' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $targets = self::getTargets();
                $keys = array_keys($targets);

                foreach ($targets as $target => $className) {
                    $choice = array_search($target, $keys);

                    $runner->runMaker(
                        [
                            // Validator name.
                            $className,
                            // Target type.
                            $choice,
                        ],
                        '--target=invalid'
                    );

                    $expectedPath = \dirname(__DIR__)."/fixtures/make-validator/expected/$className.php";
                    $generatedPath = $runner->getPath("src/Validator/$className.php");

                    self::assertSame(file_get_contents($expectedPath), file_get_contents($generatedPath));
                }
            }),
        ];

        yield 'it_makes_validator_constraint_with_invalid_target_and_choice_value' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $targets = self::getTargets();

                foreach ($targets as $target => $className) {
                    $runner->runMaker(
                        [
                            // Validator name.
                            $className,
                            // Target type.
                            $target,
                        ],
                        '--target=invalid'
                    );

                    $expectedPath = \dirname(__DIR__)."/fixtures/make-validator/expected/$className.php";
                    $generatedPath = $runner->getPath("src/Validator/$className.php");

                    self::assertSame(file_get_contents($expectedPath), file_get_contents($generatedPath));
                }
            }),
        ];
    }

    /**
     * @return array<value-of<TargetEnum>, string>
     */
    private static function getTargets(): array
    {
        return [
            'property' => 'WithTargetProperty',
            'method' => 'WithTargetMethod',
            'class' => 'WithTargetClass',
        ];
    }
}
