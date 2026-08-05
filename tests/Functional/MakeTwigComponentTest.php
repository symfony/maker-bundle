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

use Symfony\Bundle\MakerBundle\Maker\MakeTwigComponent;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\UsesProfile;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeTwigComponent::class, profile: Profiles::MEGA)]
final class MakeTwigComponentTest extends MakerTestCase
{
    #[LegacyCase('MakeTwigComponentTest::it_generates_twig_component')]
    #[UsesProfile(Profiles::GUARD)]
    public function testItGeneratesTwigComponent()
    {
        $this->app->runMaker()
            ->answer('The name of your Twig component', 'Alert')
            ->answerIfAsked('Make this a Live component', 'no')
            ->run()
            ->assertCreated(
                'src/Twig/Components/Alert.php',
                'templates/components/Alert.html.twig',
            )
            ->assertOutputContains('To render the component, use <twig:Alert />.');

        $this->app->runGeneratedTests('it_generates_twig_component.php', replace: ['{name}' => 'Alert'], covers: [
            'src/Twig/Components/Alert.php',
            'templates/components/Alert.html.twig',
        ]);
    }

    #[LegacyCase('MakeTwigComponentTest::it_generates_twig_component_in_non_default_namespace')]
    #[UsesProfile(Profiles::GUARD)]
    public function testItGeneratesTwigComponentInNonDefaultNamespace()
    {
        $this->app->copyFixture('custom_twig_component.yaml', 'config/packages/twig_component.yaml');

        $this->app->runMaker()
            ->answer('The name of your Twig component', 'Alert')
            ->answerIfAsked('Make this a Live component', 'no')
            ->run()
            ->assertCreated(
                'src/Site/Twig/Components/Alert.php',
                'templates/components/Alert.html.twig',
            )
            ->assertOutputContains('To render the component, use <twig:Alert />.');

        $this->app->runGeneratedTests('it_generates_twig_component.php', replace: ['{name}' => 'Alert'], covers: [
            'src/Site/Twig/Components/Alert.php',
            'templates/components/Alert.html.twig',
        ]);
    }

    #[LegacyCase('MakeTwigComponentTest::it_generates_pascal_case_twig_component')]
    #[UsesProfile(Profiles::GUARD)]
    public function testItGeneratesPascalCaseTwigComponent()
    {
        $this->app->runMaker()
            ->answer('The name of your Twig component', 'FormInput')
            ->answerIfAsked('Make this a Live component', 'no')
            ->run()
            ->assertCreated(
                'src/Twig/Components/FormInput.php',
                'templates/components/FormInput.html.twig',
            )
            ->assertOutputContains('To render the component, use <twig:FormInput />.');

        $this->app->runGeneratedTests('it_generates_twig_component.php', replace: ['{name}' => 'FormInput'], covers: [
            'src/Twig/Components/FormInput.php',
            'templates/components/FormInput.html.twig',
        ]);
    }

    #[LegacyCase('MakeTwigComponentTest::it_generates_live_component')]
    public function testItGeneratesLiveComponent()
    {
        $this->app->runMaker()
            ->answer('The name of your Twig component', 'Alert')
            ->answer('Make this a Live component', 'y')
            ->run()
            ->assertCreated(
                'src/Twig/Components/Alert.php',
                'templates/components/Alert.html.twig',
            )
            ->assertOutputContains('To render the component, use <twig:Alert />.');

        $this->app->runGeneratedTests('it_generates_live_component.php', replace: ['{name}' => 'Alert'], covers: [
            'src/Twig/Components/Alert.php',
            'templates/components/Alert.html.twig',
        ]);
    }

    #[LegacyCase('MakeTwigComponentTest::it_generates_pascal_case_live_component')]
    public function testItGeneratesPascalCaseLiveComponent()
    {
        $this->app->runMaker()
            ->answer('The name of your Twig component', 'FormInput')
            ->answer('Make this a Live component', 'y')
            ->run()
            ->assertCreated(
                'src/Twig/Components/FormInput.php',
                'templates/components/FormInput.html.twig',
            )
            ->assertOutputContains('To render the component, use <twig:FormInput />.');

        $this->app->runGeneratedTests('it_generates_live_component.php', replace: ['{name}' => 'FormInput'], covers: [
            'src/Twig/Components/FormInput.php',
            'templates/components/FormInput.html.twig',
        ]);
    }

    #[LegacyCase('MakeTwigComponentTest::it_generates_live_component_on_subdirectory')]
    public function testItGeneratesLiveComponentOnSubdirectory()
    {
        $this->app->runMaker()
            ->answer('The name of your Twig component', 'Form\\Input')
            ->answer('Make this a Live component', 'y')
            ->run()
            ->assertCreated(
                'src/Twig/Components/Form/Input.php',
                'templates/components/Form/Input.html.twig',
            )
            ->assertOutputContains('To render the component, use <twig:Form:Input />.');

        $this->app->runGeneratedTests('it_generates_live_component.php', replace: ['{name}' => 'Form:Input'], covers: [
            'src/Twig/Components/Form/Input.php',
            'templates/components/Form/Input.html.twig',
        ]);
    }

    /**
     * Lean guard: only meaningful because the twig-component profile really
     * lacks symfony/ux-live-component.
     */
    #[LegacyCase(null)]
    #[UsesProfile(Profiles::GUARD)]
    public function testItFailsWithoutLiveComponentPackage()
    {
        $result = $this->app->runMaker()
            ->arguments('--live')
            ->answer('The name of your Twig component', 'Alert')
            ->allowFailure()
            ->run();

        self::assertNotSame(0, $result->exitCode());
        $result->assertOutputContains('You must install symfony/ux-live-component');

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            self::assertFalse(\Composer\InstalledVersions::isInstalled('symfony/ux-live-component'));
            PHP);
    }
}
