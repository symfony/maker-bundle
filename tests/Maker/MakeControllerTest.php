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

use Symfony\Bundle\MakerBundle\Maker\MakeController;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;

class MakeControllerTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeController::class;
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_generates_a_controller' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'FooBar',
                ]);

                $this->assertContainsCount('created: ', $output, 1);

                $this->runControllerTest($runner, 'it_generates_a_controller.php');
            }),
        ];

        yield 'it_generates_a_controller_with_twig' => [$this->createMakerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'FooTwig',
                ]);

                $this->runControllerTest($runner, 'it_generates_a_controller_with_twig.php');
            }),
        ];

        yield 'it_generates_a_controller_with_twig_no_base_template' => [$this->createMakerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $runner->deleteFile('templates/base.html.twig');

                $runner->runMaker([
                    // controller class name
                    'FooTwig',
                ]);

                $this->runControllerTest($runner, 'it_generates_a_controller_with_twig.php');
            }),
        ];

        yield 'it_generates_a_controller_with_without_template' => [$this->createMakerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $runner->deleteFile('templates/base.html.twig');

                $output = $runner->runMaker([
                    // controller class name
                    'FooNoTemplate',
                ], '--no-template');

                // make sure the template was not configured
                $this->assertContainsCount('created: ', $output, 1);
                $this->assertStringContainsString('src/Controller/FooNoTemplateController.php', $output);
                $this->assertStringNotContainsString('templates/foo_no_template/index.html.twig', $output);
            }),
        ];

        yield 'it_generates_a_controller_in_sub_namespace' => [$this->createMakerTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'Admin\\FooBar',
                ]);

                $this->assertFileExists($runner->getPath('src/Controller/Admin/FooBarController.php'));
                $this->assertStringContainsString('src/Controller/Admin/FooBarController.php', $output);
            }),
        ];

        yield 'it_generates_a_controller_in_sub_namespace_with_template' => [$this->createMakerTest()
            ->addExtraDependencies('twig')
           ->run(function (MakerTestRunner $runner) {
               $output = $runner->runMaker([
                   // controller class name
                   'Admin\\FooBar',
               ]);

               $this->assertFileExists($runner->getPath('templates/admin/foo_bar/index.html.twig'));
           }),
       ];

        yield 'it_generates_a_controller_with_full_custom_namespace' => [$this->createMakerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    '\App\Foo\Bar\CoolController',
                ]);

                $this->assertStringContainsString('src/Foo/Bar/CoolController.php', $output);
                $this->assertStringContainsString('templates/foo/bar/cool/index.html.twig', $output);
            }),
        ];

        yield 'it_generates_a_controller_with_invoke' => [$this->createMakerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'FooInvokable',
                ], '--invokable');

                $this->assertStringContainsString('src/Controller/FooInvokableController.php', $output);
                $this->assertStringContainsString('templates/foo_invokable.html.twig', $output);
                $this->runControllerTest($runner, 'it_generates_an_invokable_controller.php');
            }),
        ];

        yield 'it_generates_a_controller_with_invoke_in_sub_namespace' => [$this->createMakerTest()
            ->addExtraDependencies('twig')
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // controller class name
                    'Admin\\FooInvokable',
                ], '--invokable');

                $this->assertStringContainsString('src/Controller/Admin/FooInvokableController.php', $output);
                $this->assertStringContainsString('templates/admin/foo_invokable.html.twig', $output);
            }),
        ];
    }

    private function runControllerTest(MakerTestRunner $runner, string $filename): void
    {
        $runner->copy(
            'make-controller/tests/'.$filename,
            'tests/GeneratedControllerTest.php'
        );

        $runner->runTests();
    }
}
