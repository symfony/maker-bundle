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

use Symfony\Bundle\MakerBundle\Maker\MakeTwigComponent;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeTwigComponentTest extends MakerTestCase
{
    public function getTestDetails(): \Generator
    {
        yield 'it_generates_twig_component' => [$this->createMakerTest()
            ->addExtraDependencies('symfony/ux-twig-component', 'symfony/twig-bundle')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker(['Alert']);

                $this->assertStringContainsString('created: src/Twig/Components/AlertComponent.php', $output);
                $this->assertStringContainsString('created: templates/components/alert.html.twig', $output);
                $this->assertStringContainsString("To render the component, use {{ component('alert') }}.", $output);

                $runner->copy(
                    'make-twig-component/tests/it_generates_twig_component.php',
                    'tests/GeneratedTwigComponentTest.php'
                );
                $runner->replaceInFile('tests/GeneratedTwigComponentTest.php', '{name}', 'alert');
                $runner->runTests();
            }),
        ];

        yield 'it_generates_pascal_case_twig_component' => [$this->createMakerTest()
            ->addExtraDependencies('symfony/ux-twig-component', 'symfony/twig-bundle')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker(['FormInput']);

                $this->assertStringContainsString('created: src/Twig/Components/FormInputComponent.php', $output);
                $this->assertStringContainsString('created: templates/components/form_input.html.twig', $output);
                $this->assertStringContainsString("To render the component, use {{ component('form_input') }}.", $output);

                $runner->copy(
                    'make-twig-component/tests/it_generates_twig_component.php',
                    'tests/GeneratedTwigComponentTest.php'
                );
                $runner->replaceInFile('tests/GeneratedTwigComponentTest.php', '{name}', 'form_input');
                $runner->runTests();
            }),
        ];

        yield 'it_generates_live_component' => [$this->createMakerTest()
            ->addExtraDependencies('symfony/ux-live-component', 'symfony/twig-bundle')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker(['Alert']);

                $this->assertStringContainsString('created: src/Twig/Components/AlertComponent.php', $output);
                $this->assertStringContainsString('created: templates/components/alert.html.twig', $output);
                $this->assertStringContainsString("To render the component, use {{ component('alert') }}.", $output);

                $runner->copy(
                    'make-twig-component/tests/it_generates_live_component.php',
                    'tests/GeneratedLiveComponentTest.php'
                );
                $runner->replaceInFile('tests/GeneratedLiveComponentTest.php', '{name}', 'alert');
                $runner->runTests();
            }),
        ];

        yield 'it_generates_pascal_case_live_component' => [$this->createMakerTest()
            ->addExtraDependencies('symfony/ux-live-component', 'symfony/twig-bundle')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker(['FormInput']);

                $this->assertStringContainsString('created: src/Twig/Components/FormInputComponent.php', $output);
                $this->assertStringContainsString('created: templates/components/form_input.html.twig', $output);
                $this->assertStringContainsString("To render the component, use {{ component('form_input') }}.", $output);

                $runner->copy(
                    'make-twig-component/tests/it_generates_live_component.php',
                    'tests/GeneratedLiveComponentTest.php'
                );
                $runner->replaceInFile('tests/GeneratedLiveComponentTest.php', '{name}', 'form_input');
                $runner->runTests();
            }),
        ];
    }

    protected function getMakerClass(): string
    {
        return MakeTwigComponent::class;
    }
}
