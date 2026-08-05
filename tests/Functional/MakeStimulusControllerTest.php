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

use Symfony\Bundle\MakerBundle\Maker\MakeStimulusController;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\WindowsSmoke;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeStimulusController::class, profile: Profiles::MEGA)]
final class MakeStimulusControllerTest extends MakerTestCase
{
    #[LegacyCase('MakeStimulusControllerTest::it_generates_stimulus_controller')]
    public function testItGeneratesStimulusController()
    {
        $this->app->runMaker()
            ->answer('The name of the Stimulus controller', 'default')
            ->answer('Language', 'js')
            ->answer('Do you want to include targets?', 'no')
            ->answer('Do you want to include values?', 'no')
            ->acceptDefault('Do you want to add classes?')
            ->run()
            ->assertCreated('assets/controllers/default_controller.js');

        $this->app->assertStimulusControllerRuns('assets/controllers/default_controller.js');
    }

    #[LegacyCase('MakeStimulusControllerTest::it_generates_stimulus_controller_with_targets')]
    public function testItGeneratesStimulusControllerWithTargets()
    {
        $this->app->runMaker()
            ->answer('The name of the Stimulus controller', 'with_targets')
            ->answer('Language', 'js')
            ->answer('Do you want to include targets?', 'yes')
            ->answer('New target name', 'results')
            ->answer('Add another target', 'messages')
            ->answer('Add another target', 'errors')
            ->acceptDefault('Add another target')
            ->answer('Do you want to include values?', 'no')
            ->acceptDefault('Do you want to add classes?')
            ->run()
            ->assertCreated('assets/controllers/with_targets_controller.js');

        $this->app->assertFileMatchesFixture('assets/controllers/with_targets_controller.js', 'with_targets.js');

        $this->app->assertStimulusControllerRuns('assets/controllers/with_targets_controller.js', [
            'targets' => ['results', 'messages', 'errors'],
        ]);
    }

    #[LegacyCase('MakeStimulusControllerTest::it_generates_stimulus_controller_without_targets')]
    public function testItGeneratesStimulusControllerWithoutTargets()
    {
        $this->app->runMaker()
            ->answer('The name of the Stimulus controller', 'without_targets')
            ->answer('Language', 'js')
            ->answer('Do you want to include targets?', 'no')
            ->answer('Do you want to include values?', 'no')
            ->acceptDefault('Do you want to add classes?')
            ->run()
            ->assertCreated('assets/controllers/without_targets_controller.js');

        $this->app->assertFileMatchesFixture('assets/controllers/without_targets_controller.js', 'without_targets.js');

        $this->app->assertStimulusControllerRuns('assets/controllers/without_targets_controller.js', [
            'targets' => [],
            'values' => [],
            'classes' => [],
        ]);
    }

    #[LegacyCase('MakeStimulusControllerTest::it_generates_stimulus_controller_with_values')]
    public function testItGeneratesStimulusControllerWithValues()
    {
        $this->app->runMaker()
            ->answer('The name of the Stimulus controller', 'with_values')
            ->answer('Language', 'js')
            ->answer('Do you want to include targets?', 'no')
            ->answer('Do you want to include values?', 'yes')
            ->answer('New value name', 'min')
            ->answer('Value type', 'Number')
            ->answer('Add another value', 'email')
            ->answer('Value type', 'String')
            ->acceptDefault('Add another value')
            ->acceptDefault('Do you want to add classes?')
            ->run()
            ->assertCreated('assets/controllers/with_values_controller.js');

        $this->app->assertFileMatchesFixture('assets/controllers/with_values_controller.js', 'with_values.js');

        $this->app->assertStimulusControllerRuns('assets/controllers/with_values_controller.js', [
            'values' => ['min', 'email'],
        ]);
    }

    #[LegacyCase('MakeStimulusControllerTest::it_generates_stimulus_controller_with_classes')]
    public function testItGeneratesStimulusControllerWithClasses()
    {
        $this->app->runMaker()
            ->answer('The name of the Stimulus controller', 'with_classes')
            ->answer('Language', 'js')
            ->answer('Do you want to include targets?', 'no')
            ->answer('Do you want to include values?', 'no')
            ->answer('Do you want to add classes?', 'yes')
            ->answer('New class name', 'foo')
            ->answer('Add another class', 'bar')
            ->acceptDefault('Add another class')
            ->run()
            ->assertCreated('assets/controllers/with_classes_controller.js');

        $this->app->assertFileMatchesFixture('assets/controllers/with_classes_controller.js', 'with_classes.js');

        $this->app->assertStimulusControllerRuns('assets/controllers/with_classes_controller.js', [
            'classes' => ['foo', 'bar'],
        ]);
    }

    #[LegacyCase('MakeStimulusControllerTest::it_generates_stimulus_controller_with_targets_values_and_classes')]
    #[WindowsSmoke]
    public function testItGeneratesStimulusControllerWithTargetsValuesAndClasses()
    {
        $this->app->runMaker()
            ->answer('The name of the Stimulus controller', 'with_targets_values_classes')
            ->answer('Language', 'js')
            ->answer('Do you want to include targets?', 'yes')
            ->answer('New target name', 'aaa')
            ->answer('Add another target', 'bbb')
            ->acceptDefault('Add another target')
            ->answer('Do you want to include values?', 'yes')
            ->answer('New value name', 'ccc')
            ->answer('Value type', 'Number')
            ->answer('Add another value', 'ddd')
            ->answer('Value type', 'String')
            ->acceptDefault('Add another value')
            ->answer('Do you want to add classes?', 'yes')
            ->answer('New class name', 'eee')
            ->answer('Add another class', 'fff')
            ->acceptDefault('Add another class')
            ->run()
            ->assertCreated('assets/controllers/with_targets_values_classes_controller.js');

        $this->app->assertFileMatchesFixture('assets/controllers/with_targets_values_classes_controller.js', 'with_targets_values_classes.js');

        $this->app->assertStimulusControllerRuns('assets/controllers/with_targets_values_classes_controller.js', [
            'targets' => ['aaa', 'bbb'],
            'values' => ['ccc', 'ddd'],
            'classes' => ['eee', 'fff'],
        ]);
    }

    #[LegacyCase('MakeStimulusControllerTest::it_generates_typescript_stimulus_controller_interactively')]
    public function testItGeneratesTypescriptStimulusControllerInteractively()
    {
        $this->app->runMaker()
            ->answer('The name of the Stimulus controller', 'typescript')
            ->answer('Language', 'ts')
            ->answer('Do you want to include targets?', 'no')
            ->answer('Do you want to include values?', 'no')
            ->acceptDefault('Do you want to add classes?')
            ->run()
            ->assertCreatedExactly(['assets/controllers/typescript_controller.ts']);

        $this->app->assertStimulusControllerRuns('assets/controllers/typescript_controller.ts');
    }

    #[LegacyCase('MakeStimulusControllerTest::it_generates_typescript_stimulus_controller_when_option_is_set')]
    public function testItGeneratesTypescriptStimulusControllerWhenOptionIsSet()
    {
        $this->app->runMaker()
            ->arguments('--typescript')
            ->answer('The name of the Stimulus controller', 'typescript')
            ->answer('Do you want to include targets?', 'no')
            ->answer('Do you want to include values?', 'no')
            ->acceptDefault('Do you want to add classes?')
            ->run()
            ->assertCreatedExactly(['assets/controllers/typescript_controller.ts']);

        $this->app->assertStimulusControllerRuns('assets/controllers/typescript_controller.ts');
    }

    #[LegacyCase('MakeStimulusControllerTest::it_displays_controller_basic_usage_example')]
    public function testItDisplaysControllerBasicUsageExample()
    {
        $result = $this->app->runMaker()
            ->answer('The name of the Stimulus controller', 'fooBar')
            ->answer('Language', 'js')
            ->answer('Do you want to include targets?', 'no')
            ->answer('Do you want to include values?', 'no')
            ->acceptDefault('Do you want to add classes?')
            ->run();

        $result->assertOutputContains('- Use the controller in your templates:');
        foreach (explode("\n", <<<HTML
                <div data-controller="foo-bar">
                    <!-- ... -->
                </div>
            HTML) as $line) {
            $result->assertOutputContains(trim($line));
        }

        $this->app->assertStimulusControllerRuns('assets/controllers/foo_bar_controller.js');
    }

    #[LegacyCase('MakeStimulusControllerTest::it_displays_controller_complete_usage_example')]
    public function testItDisplaysControllerCompleteUsageExample()
    {
        $result = $this->app->runMaker()
            ->answer('The name of the Stimulus controller', 'fooBar')
            ->answer('Language', 'js')
            ->answer('Do you want to include targets?', 'yes')
            ->answer('New target name', 'firstOne')
            ->answer('Add another target', 'secondOne')
            ->acceptDefault('Add another target')
            ->answer('Do you want to include values?', 'yes')
            ->answer('New value name', 'minItems')
            ->answer('Value type', 'Number')
            ->answer('Add another value', 'email')
            ->answer('Value type', 'String')
            ->acceptDefault('Add another value')
            ->answer('Do you want to add classes?', 'yes')
            ->answer('New class name', 'isVisible')
            ->answer('Add another class', 'hidden')
            ->acceptDefault('Add another class')
            ->run();

        $result->assertOutputContains('- Use the controller in your templates:');
        foreach (explode("\n", <<<HTML
                <div data-controller="foo-bar"
                    data-foo-bar-min-items-value="123"
                    data-foo-bar-email-value="abc"
                    data-foo-bar-is-visible-class="isVisible"
                    data-foo-bar-hidden-class="hidden"
                >
                    <div data-foo-bar-target="firstOne"></div>
                    <div data-foo-bar-target="secondOne"></div>
                    <!-- ... -->
                </div>
            HTML) as $line) {
            $result->assertOutputContains(trim($line));
        }

        $this->app->assertStimulusControllerRuns('assets/controllers/foo_bar_controller.js', [
            'targets' => ['firstOne', 'secondOne'],
            'values' => ['minItems', 'email'],
            'classes' => ['isVisible', 'hidden'],
        ]);
    }
}
