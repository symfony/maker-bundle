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

use Symfony\Bundle\MakerBundle\Maker\MakeCommand;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\UsesProfile;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\WindowsSmoke;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeCommand::class, profile: Profiles::MEGA)]
final class MakeCommandTest extends MakerTestCase
{
    // the legacy no_attributes/with_attributes split only mattered on Symfony
    // versions without #[AsCommand]; on every supported version the two cases
    // were byte-identical runs, so one method carries both lineages
    #[LegacyCase('MakeCommandTest::it_makes_a_command_no_attributes')]
    #[LegacyCase('MakeCommandTest::it_makes_a_command_with_attributes')]
    #[WindowsSmoke]
    public function testItMakesACommand()
    {
        $this->app->runMaker()
            ->answer('Choose a command name', 'app:foo')
            ->run()
            ->assertCreated('src/Command/FooCommand.php');

        $this->app->assertFileContains('src/Command/FooCommand.php', 'use Symfony\Component\Console\Attribute\AsCommand;');
        $this->app->assertFileContains('src/Command/FooCommand.php', '#[AsCommand(');

        $this->app->runGeneratedTests('it_makes_a_command.php', covers: ['src/Command/FooCommand.php']);
    }

    #[LegacyCase('MakeCommandTest::it_makes_a_command_in_custom_namespace')]
    #[UsesProfile(Profiles::MEGA_CUSTOM_NAMESPACE)]
    #[WindowsSmoke]
    public function testItMakesACommandInCustomNamespace()
    {
        $this->app->runMaker()
            ->answer('Choose a command name', 'app:foo')
            ->run()
            ->assertCreated('src/Command/FooCommand.php');

        $this->app->runGeneratedTests('it_makes_a_command_in_custom_namespace.php', covers: ['src/Command/FooCommand.php']);
    }
}
