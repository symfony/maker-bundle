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

use Symfony\Bundle\MakerBundle\Maker\MakeVoter;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\UsesProfile;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\WindowsSmoke;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Process\CommandResult;

/**
 * Not testing a maker directly, but how generated files are linted.
 * MakeVoter is simply the smallest maker to lint through.
 */
#[MakerTest(maker: MakeVoter::class, profile: Profiles::PHPCSFIXER)]
#[WindowsSmoke]
final class TemplateLinterTest extends MakerTestCase
{
    #[LegacyCase('TemplateLinterTest::lints_templates_with_custom_php_cs_fixer_and_config')]
    public function testLintsTemplatesWithCustomPhpCsFixerAndConfig()
    {
        $this->app->copyFixture('linter/php-cs-fixer.test.php', 'php-cs-fixer.test.php');
        $this->app->replaceInFile(
            '.env',
            '###< symfony/framework-bundle ###',
            <<< 'EOT'
                ###< symfony/framework-bundle ###
                MAKER_PHP_CS_FIXER_CONFIG_PATH=php-cs-fixer.test.php
                MAKER_PHP_CS_FIXER_BINARY_PATH=vendor/bin/php-cs-fixer
                EOT,
        );

        $result = $this->runVerboseMaker();

        $this->app->assertFileContains('src/Security/Voter/FooBarVoter.php', 'Linted by custom php-cs-config');
        $result->assertOutputContains('System PHP-CS-Fixer (vendor/bin/php-cs-fixer) & System PHP-CS-Fixer Configuration (php-cs-fixer.test.php)');

        $this->runVoterAssertions();
    }

    #[LegacyCase('TemplateLinterTest::lints_templates_with_flex_generated_config_file')]
    public function testLintsTemplatesWithFlexGeneratedConfigFile()
    {
        $this->app->replaceInFile(
            '.php-cs-fixer.dist.php',
            '\'@Symfony\' => true,',
            <<< 'EOT'
                '@Symfony' => true,
                        'header_comment' => [
                            'header' => 'Linted with stock php-cs-config',
                        ],
                EOT,
        );

        $result = $this->runVerboseMaker();

        $this->app->assertFileContains('src/Security/Voter/FooBarVoter.php', 'Linted with stock php-cs-config');
        $result->assertOutputContains('Bundled PHP-CS-Fixer & System PHP-CS-Fixer Configuration (.php-cs-fixer.dist.php)');

        $this->runVoterAssertions();
    }

    #[LegacyCase('TemplateLinterTest::lints_templates_with_bundled_php_cs_fixer')]
    #[UsesProfile(Profiles::MEGA)]
    public function testLintsTemplatesWithBundledPhpCsFixer()
    {
        $result = $this->runVerboseMaker();

        $result->assertOutputContains('Bundled PHP-CS-Fixer & Bundled PHP-CS-Fixer Configuration');

        // lean guard: only meaningful because php-cs-fixer is really absent
        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            self::assertFalse(\Composer\InstalledVersions::isInstalled('php-cs-fixer/shim'));
            PHP);

        $this->runVoterAssertions();
    }

    /**
     * The "Linting Generated Files With:" summary only prints on -v. The
     * legacy suite asserted it without -v and only passed because
     * simple-phpunit leaked SHELL_VERBOSITY=1 into the maker subprocess —
     * an accident the new harness must not rely on.
     */
    private function runVerboseMaker(): CommandResult
    {
        return $this->app->runMaker()
            ->arguments('-v')
            ->answer('The name of the security voter class', 'FooBar')
            ->run()
            ->assertCreated('src/Security/Voter/FooBarVoter.php');
    }

    private function runVoterAssertions(): void
    {
        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $voter = new \App\Security\Voter\FooBarVoter();

            $result = $voter->vote(
                new \Symfony\Component\Security\Core\Authentication\Token\NullToken(),
                new \stdClass(),
                [\App\Security\Voter\FooBarVoter::VIEW],
            );

            self::assertSame(\Symfony\Component\Security\Core\Authorization\Voter\VoterInterface::ACCESS_ABSTAIN, $result);
            PHP,
            covers: ['src/Security/Voter/FooBarVoter.php'],
        );
    }
}
