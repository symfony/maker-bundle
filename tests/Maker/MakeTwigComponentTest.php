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
            ->skipOnSymfony7() // legacy: remove when ux-twig-component supports Symfony 7
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker(['Alert']);

                $this->assertStringContainsString('src/Twig/Components/Alert.php', $output);
                $this->assertStringContainsString('templates/components/Alert.html.twig', $output);
                $this->assertStringContainsString("To render the component, use {{ component('Alert') }}.", $output);

                $runner->copy(
                    'make-twig-component/tests/it_generates_twig_component.php',
                    'tests/GeneratedTwigComponentTest.php'
                );
                $runner->replaceInFile('tests/GeneratedTwigComponentTest.php', '{name}', 'Alert');
                $runner->runTests();
            }),
        ];

        yield 'it_generates_pascal_case_twig_component' => [$this->createMakerTest()
            ->addExtraDependencies('symfony/ux-twig-component', 'symfony/twig-bundle')
            ->skipOnSymfony7() // legacy: remove when ux-twig-component supports Symfony 7
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker(['FormInput']);

                $this->assertStringContainsString('src/Twig/Components/FormInput.php', $output);
                $this->assertStringContainsString('templates/components/FormInput.html.twig', $output);
                $this->assertStringContainsString("To render the component, use {{ component('FormInput') }}.", $output);

                $runner->copy(
                    'make-twig-component/tests/it_generates_twig_component.php',
                    'tests/GeneratedTwigComponentTest.php'
                );
                $runner->replaceInFile('tests/GeneratedTwigComponentTest.php', '{name}', 'FormInput');
                $runner->runTests();
            }),
        ];

        yield 'it_generates_live_component' => [$this->createMakerTest()
            ->addExtraDependencies('symfony/ux-live-component', 'symfony/twig-bundle')
            ->skipOnSymfony7() // legacy: remove when ux-twig-component supports Symfony 7
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker(['Alert']);

                $this->assertStringContainsString('src/Twig/Components/Alert.php', $output);
                $this->assertStringContainsString('templates/components/Alert.html.twig', $output);
                $this->assertStringContainsString("To render the component, use {{ component('Alert') }}.", $output);

                $runner->copy(
                    'make-twig-component/tests/it_generates_live_component.php',
                    'tests/GeneratedLiveComponentTest.php'
                );
                $runner->replaceInFile('tests/GeneratedLiveComponentTest.php', '{name}', 'Alert');
                $runner->runTests();
            }),
        ];

        yield 'it_generates_pascal_case_live_component' => [$this->createMakerTest()
            ->addExtraDependencies('symfony/ux-live-component', 'symfony/twig-bundle')
            ->skipOnSymfony7() // legacy: remove when ux-twig-component supports Symfony 7
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker(['FormInput']);

                $this->assertStringContainsString('src/Twig/Components/FormInput.php', $output);
                $this->assertStringContainsString('templates/components/FormInput.html.twig', $output);
                $this->assertStringContainsString("To render the component, use {{ component('FormInput') }}.", $output);

                $runner->copy(
                    'make-twig-component/tests/it_generates_live_component.php',
                    'tests/GeneratedLiveComponentTest.php'
                );
                $runner->replaceInFile('tests/GeneratedLiveComponentTest.php', '{name}', 'FormInput');
                $runner->runTests();
            }),
        ];
    }

    protected function getMakerClass(): string
    {
        return MakeTwigComponent::class;
    }
}
