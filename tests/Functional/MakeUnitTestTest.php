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

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\MakerBundle\Maker\MakeUnitTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

/**
 * @group legacy
 */
#[Group('legacy')]
#[MakerTest(maker: MakeUnitTest::class, profile: Profiles::MEGA)]
final class MakeUnitTestTest extends MakerTestCase
{
    #[LegacyCase('MakeUnitTestTest::it_makes_unit_test')]
    public function testItMakesUnitTest()
    {
        $this->app->runMaker()
            ->answer('The name of the test class', 'FooBar')
            ->run()
            ->assertCreated('tests/FooBarTest.php');

        $this->app->runGeneratedTestFile('tests/FooBarTest.php', covers: ['tests/FooBarTest.php']);
    }
}
