<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Functional;

use Symfony\Bundle\MakerBundle\Maker\MakeValidator;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\WindowsSmoke;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeValidator::class, profile: Profiles::MEGA)]
final class MakeValidatorTest extends MakerTestCase
{
    #[LegacyCase('MakeValidatorTest::it_makes_validator')]
    #[WindowsSmoke]
    public function testItMakesValidator()
    {
        $this->app->runMaker()
            ->answer('The name of the validator class', 'FooBar')
            ->run()
            ->assertCreated('src/Validator/FooBar.php', 'src/Validator/FooBarValidator.php');

        $this->app->assertFileMatchesFixture('src/Validator/FooBarValidator.php', 'expected/FooBarValidator.php');
        $this->app->assertFileMatchesFixture('src/Validator/FooBar.php', 'expected/FooBar.php');

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $validator = static::getContainer()->get('validator');

            self::assertCount(0, $validator->validate('', [new \App\Validator\FooBar()]));

            $violations = $validator->validate('some value', [new \App\Validator\FooBar()]);
            self::assertCount(1, $violations);
            self::assertStringContainsString('contains an illegal character', $violations[0]->getMessage());
            PHP,
            covers: ['src/Validator/FooBar.php', 'src/Validator/FooBarValidator.php'],
        );
    }
}
