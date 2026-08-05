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

use Symfony\Bundle\MakerBundle\Maker\MakeFunctionalTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\SkipIfEnv;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

/**
 * make:functional-test is deprecated (superseded by make:test) but keeps its
 * coverage until removal: with panther installed it generates a PantherTestCase.
 */
#[MakerTest(maker: MakeFunctionalTest::class, profile: Profiles::PANTHER)]
final class MakeFunctionalTestTest extends MakerTestCase
{
    #[LegacyCase('MakeFunctionalTestTest::it_generates_test_with_panther')]
    #[SkipIfEnv('MAKER_SKIP_PANTHER_TEST')]
    public function testItGeneratesTestWithPanther()
    {
        $this->app->copyFixture('MainController.php', 'src/Controller/MainController.php');
        $this->app->copyFixture('routes.yaml', 'config/routes.yaml');

        $this->app->runMaker()
            ->answer('The name of the test class', 'FooBar')
            ->run()
            ->assertCreated('tests/FooBarTest.php');

        $this->app->runGeneratedTestFile('tests/FooBarTest.php', covers: ['tests/FooBarTest.php']);
    }
}
