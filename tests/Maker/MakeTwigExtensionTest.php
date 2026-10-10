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

    public static function getTestDetails(): \Generator
    {
        yield 'it_makes_twig_extension' => [self::buildMakerTest()
            ->addRequiredPackageVersion('twig/twig', '>=3.21')
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        // extension class name
                        'FooBar',
                    ]
                );

                $expectedExtensionPath = \dirname(__DIR__).'/fixtures/make-twig-extension/expected/FooBarExtension.php';
                $generatedExtension = $runner->getPath('src/Twig/FooBarExtension.php');

                self::assertSame(file_get_contents($expectedExtensionPath), file_get_contents($generatedExtension));
            }),
        ];

        yield 'it_rejects_invalid_twig_extension_name_non_interactively' => [self::buildMakerTest()
            ->addRequiredPackageVersion('twig/twig', '>=3.21')
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([], '--no-interaction "Foo Bar"', allowedToFail: true);

                self::assertStringContainsString('The Twig extension class "Twig\Foo Bar" is not a valid class name.', $output);
                self::assertFileDoesNotExist($runner->getPath('src/Twig/FooBarExtension.php'));
            }),
        ];
    }
}
