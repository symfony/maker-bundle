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

/**
 * This test is not testing a maker directly. But the files generated by a maker.
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class TemplateLinterTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        // We can use any maker here - MakeVoter is the simplest for now.
        return MakeVoter::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'lints_templates_with_custom_php_cs_fixer_and_config' => [$this->createMakerTest()
            ->addExtraDependencies('friendsofphp/php-cs-fixer')
            ->run(function (MakerTestRunner $runner) {
                $runner->copy('template-linter/php-cs-fixer.test.php', 'php-cs-fixer.test.php');

                $runner->replaceInFile(
                    '.env',
                    '###< symfony/framework-bundle ###',
                    <<< 'EOT'
                        MAKER_PHP_CS_FIXER_CONFIG_PATH=php-cs-fixer.test.php
                        MAKER_PHP_CS_FIXER_BINARY_PATH=bin/php-cs-fixer
                        EOT
                );

                // Voter class name
                $runner->runMaker(['FooBar']);

                $generatedTemplate = file_get_contents($runner->getPath('src/Security/Voter/FooBarVoter.php'));

                self::assertStringContainsString('Linted by custom php-cs-config', $generatedTemplate);
            }),
        ];
    }
}