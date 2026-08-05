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

use Symfony\Bundle\MakerBundle\Maker\MakeTwigExtension;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeTwigExtension::class, profile: Profiles::MEGA)]
final class MakeTwigExtensionTest extends MakerTestCase
{
    #[LegacyCase('MakeTwigExtensionTest::it_makes_twig_extension')]
    public function testItMakesTwigExtension()
    {
        $this->app->runMaker()
            ->answer('The name of the Twig extension class', 'FooBar')
            ->run()
            ->assertCreated(
                'src/Twig/Extension/FooBarExtension.php',
                'src/Twig/Runtime/FooBarRuntime.php',
            );

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            /** @var \Twig\Environment $twig */
            $twig = static::getContainer()->get('twig');

            self::assertNotFalse($twig->getFilter('filter_name'));
            self::assertNotFalse($twig->getFunction('function_name'));

            // rendering invokes FooBarRuntime::doSomething() for real
            self::assertSame('', $twig->createTemplate("{{ 'value'|filter_name }}")->render());
            PHP,
            covers: [
                'src/Twig/Extension/FooBarExtension.php',
                'src/Twig/Runtime/FooBarRuntime.php',
            ],
        );
    }
}
