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

use Symfony\Bundle\MakerBundle\Maker\MakeTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\SkipIfEnv;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\UsesProfile;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeTest::class, profile: Profiles::BASE)]
final class MakeTestTest extends MakerTestCase
{
    #[LegacyCase('MakeTestTest::it_makes_TestCase_type')]
    public function testItMakesTestCaseType()
    {
        $this->app->runMaker()
            ->answer('Which test type would you like?', 'TestCase')
            ->answer('The name of the test class', 'FooBar')
            ->run()
            ->assertCreated('tests/FooBarTest.php');

        // the contract demands running the exact generated test file
        $this->app->runGeneratedTestFile('tests/FooBarTest.php', covers: ['tests/FooBarTest.php']);
    }

    #[LegacyCase('MakeTestTest::it_makes_KernelTestCase_type')]
    public function testItMakesKernelTestCaseType()
    {
        $this->app->copyFixture('basic_setup', '');

        $this->app->runMaker()
            ->answer('Which test type would you like?', 'KernelTestCase')
            ->answer('The name of the test class', 'FooBar')
            ->run()
            ->assertCreated('tests/FooBarTest.php');

        $this->app->runGeneratedTestFile('tests/FooBarTest.php', covers: ['tests/FooBarTest.php']);
    }

    #[LegacyCase('MakeTestTest::it_makes_WebTestCase_type')]
    public function testItMakesWebTestCaseType()
    {
        $this->app->copyFixture('basic_setup', '');

        $this->app->runMaker()
            ->answer('Which test type would you like?', 'WebTestCase')
            ->answer('The name of the test class', 'FooBar')
            ->run()
            ->assertCreated('tests/FooBarTest.php');

        $this->app->runGeneratedTestFile('tests/FooBarTest.php', covers: ['tests/FooBarTest.php']);
    }

    #[LegacyCase('MakeTestTest::it_makes_PantherTestCase_type')]
    #[UsesProfile(Profiles::PANTHER)]
    #[SkipIfEnv('MAKER_SKIP_PANTHER_TEST')]
    public function testItMakesPantherTestCaseType()
    {
        $this->app->copyFixture('basic_setup', '');

        $this->app->runMaker()
            ->answer('Which test type would you like?', 'PantherTestCase')
            ->answer('The name of the test class', 'FooBar')
            ->run()
            ->assertCreated('tests/FooBarTest.php');

        // drives a real browser against the app (driver downloaded at profile build)
        $this->app->runGeneratedTestFile('tests/FooBarTest.php', covers: ['tests/FooBarTest.php']);
    }
}
