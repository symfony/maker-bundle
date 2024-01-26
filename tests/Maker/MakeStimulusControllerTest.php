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

        yield 'it_generates_typescript_stimulus_controller' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [
                        'typescript', // controller name
                        'ts', // controller language
                        'no', // do not add targets
                    ]);

                $this->assertFileExists($runner->getPath('assets/controllers/typescript_controller.ts'));
            }),
        ];
    }
}
