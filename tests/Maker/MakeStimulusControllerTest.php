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

use Symfony\Bundle\MakerBundle\Maker\MakeStimulusController;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeStimulusControllerTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeStimulusController::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_generates_stimulus_controller' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        'default', // controller name
                    ],
                );

                $generatedFilePath = $runner->getPath('assets/controllers/default_controller.js');
                $this->assertFileExists($generatedFilePath);
            }),
        ];

        yield 'it_generates_stimulus_controller_with_targets' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        'with_targets', // controller name
                        'js', // controller language
                        'yes', // add targets
                        'results', // first target
                        'messages', // second target
                        'errors', // third target
                        '', // empty input to stop adding targets
                    ]);

                $generatedFilePath = $runner->getPath('assets/controllers/with_targets_controller.js');

                $this->assertFileExists($generatedFilePath);

                $generatedFileContents = file_get_contents($generatedFilePath);
                $expectedContents = file_get_contents(__DIR__.'/../fixtures/make-stimulus-controller/with_targets.js');

                $this->assertSame(
                    $expectedContents,
                    $generatedFileContents
                );
            }),
        ];

        yield 'it_generates_stimulus_controller_without_targets' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        'without_targets', // controller name
                        'js', // controller language
                        'no', // do not add targets
                    ]);

                $generatedFilePath = $runner->getPath('assets/controllers/without_targets_controller.js');

                $this->assertFileExists($generatedFilePath);

                $generatedFileContents = file_get_contents($generatedFilePath);
                $expectedContents = file_get_contents(__DIR__.'/../fixtures/make-stimulus-controller/without_targets.js');

                $this->assertSame(
                    $expectedContents,
                    $generatedFileContents
                );
            }),
        ];

        yield 'it_generates_stimulus_controller_with_values' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        'with_values', // controller name
                        'js', // controller language
                        'no', // no targets
                        'yes', // values
                        'min', // first value
                        'Number', // first value type
                        'email', // second values
                        'String', // second value type
                        '', // empty input to stop adding values
                    ]);

                $generatedFilePath = $runner->getPath('assets/controllers/with_values_controller.js');

                $this->assertFileExists($generatedFilePath);

                $generatedFileContents = file_get_contents($generatedFilePath);
                $expectedContents = file_get_contents(__DIR__.'/../fixtures/make-stimulus-controller/with_values.js');

                $this->assertSame(
                    $expectedContents,
                    $generatedFileContents
                );
            }),
        ];

        yield 'it_generates_stimulus_controller_with_classes' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        'with_classes', // controller name
                        'js', // use default extension (js)
                        'no', // do not add targets
                        'no', // do not add values
                        'yes', // add classes
                        'foo', // first class
                        'bar', // second class
                        '', // empty input to stop adding classes
                    ]);

                $generatedFilePath = $runner->getPath('assets/controllers/with_classes_controller.js');

                $this->assertFileExists($generatedFilePath);

                $generatedFileContents = file_get_contents($generatedFilePath);
                $expectedContents = file_get_contents(__DIR__.'/../fixtures/make-stimulus-controller/with_classes.js');

                $this->assertSame(
                    $expectedContents,
                    $generatedFileContents
                );
            }),
        ];

        yield 'it_generates_stimulus_controller_with_targets_values_and_classes' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        'with_targets_values_classes',
                        'js',
                        'yes', // add targets
                        'aaa',
                        'bbb',
                        '',    // end
                        'yes', // add values
                        'ccc',
                        'Number',
                        'ddd',
                        'String',
                        '',    // end
                        'yes', // add classes
                        'eee',
                        'fff',
                        '',    // end
                    ]);

                $generatedFilePath = $runner->getPath('assets/controllers/with_targets_values_classes_controller.js');

                $this->assertFileExists($generatedFilePath);

                $generatedFileContents = file_get_contents($generatedFilePath);
                $expectedContents = file_get_contents(__DIR__.'/../fixtures/make-stimulus-controller/with_targets_values_classes.js');

                $this->assertSame(
                    $expectedContents,
                    $generatedFileContents
                );
            }),
        ];

        yield 'it_generates_typescript_stimulus_controller_interactively' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        'typescript', // controller name
                        'ts', // controller language
                        'no', // do not add targets
                    ],
                );

                $this->assertFileExists($runner->getPath('assets/controllers/typescript_controller.ts'));
                $this->assertFileDoesNotExist($runner->getPath('assets/controllers/typescript_controller.js'));
            }),
        ];

        yield 'it_generates_typescript_stimulus_controller_when_option_is_set' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        'typescript', // controller name
                        // '', // language is not asked interactively
                        'no', // do not add targets
                    ],
                    ' --typescript'
                );

                $this->assertFileExists($runner->getPath('assets/controllers/typescript_controller.ts'));
                $this->assertFileDoesNotExist($runner->getPath('assets/controllers/typescript_controller.js'));
            }),
        ];

        yield 'it_displays_controller_basic_usage_example' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker(
                    [
                        'fooBar',
                        'js',
                    ],
                );

                $usageExample = <<<HTML
                        <div data-controller="foo-bar">
                            <!-- ... -->
                        </div>
                    HTML;

                $this->assertStringContainsString('- Use the controller in your templates:', $output);
                foreach (explode("\n", $usageExample) as $line) {
                    $this->assertStringContainsString($line, $output);
                }
            }),
        ];

        yield 'it_displays_controller_complete_usage_example' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker(
                    [
                        'fooBar',
                        'js',
                        'yes', // add targets
                        'firstOne',
                        'secondOne',
                        '',
                        'yes', // add values
                        'minItems',
                        'Number',
                        'email',
                        'String',
                        '',
                        'yes', // add classes
                        'isVisible',
                        'hidden',
                        '',
                    ],
                );

                $usageExample = <<<HTML
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
                    HTML;

                $this->assertStringContainsString('- Use the controller in your templates:', $output);
                foreach (explode("\n", $usageExample) as $line) {
                    $this->assertStringContainsString($line, $output);
                }
            }),
        ];
    }
}
