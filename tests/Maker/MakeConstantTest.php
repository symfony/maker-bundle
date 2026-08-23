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

use Symfony\Bundle\MakerBundle\Maker\MakeConstant;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeConstantTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeConstant::class;
    }

    public static function getTestDetails(): \Generator
    {
        yield 'it_adds_a_constant_interactively' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy('make-constant/Product.php', 'src/Entity/Product.php');

                $output = $runner->runMaker([
                    // class
                    'Product',
                    // constant name
                    'STATUS_PENDING',
                    // constant value
                    'pending',
                ]);

                self::assertStringContainsString('Success', $output);

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-constant/ProductWithConstant.php',
                    $runner->getPath('src/Entity/Product.php')
                );
            }),
        ];

        yield 'it_adds_a_typed_final_private_constant_via_options' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy('make-constant/ProductCatalog.php', 'src/Entity/ProductCatalog.php');

                $output = $runner->runMaker(
                    [],
                    'ProductCatalog MAX_ITEMS_PER_ORDER 50 --type=int --visibility=private --final --comment="Maximum number of items allowed per order"'
                );

                self::assertStringContainsString('Success', $output);

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-constant/ProductCatalogWithConstant.php',
                    $runner->getPath('src/Entity/ProductCatalog.php')
                );
            }),
        ];

        yield 'it_guesses_the_type_from_the_value_when_no_type_is_given' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy('make-constant/Product.php', 'src/Entity/Product.php');

                $runner->runMaker(
                    [],
                    'Product MAX_QUANTITY 25'
                );

                self::assertFileEquals(
                    \dirname(__DIR__).'/fixtures/make-constant/ProductWithGuessedIntConstant.php',
                    $runner->getPath('src/Entity/Product.php')
                );
            }),
        ];

        yield 'it_fails_when_the_class_does_not_exist' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker(
                    [],
                    'NotFoundClass STATUS_PENDING pending',
                    allowedToFail: true
                );

                self::assertStringContainsString('doesn\'t exist', $output);
            }),
        ];

        yield 'it_fails_when_the_constant_name_is_invalid' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy('make-constant/Product.php', 'src/Entity/Product.php');

                $output = $runner->runMaker(
                    [],
                    'Product statusPending pending',
                    allowedToFail: true
                );

                self::assertStringContainsString('UPPER_SNAKE_CASE', $output);
            }),
        ];

        yield 'it_fails_when_the_constant_already_exists' => [self::buildMakerTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->copy('make-constant/ProductCatalog.php', 'src/Entity/ProductCatalog.php');

                $output = $runner->runMaker(
                    [],
                    'ProductCatalog DEFAULT_CURRENCY USD',
                    allowedToFail: true
                );

                self::assertStringContainsString('already exists', $output);
            }),
        ];
    }
}
