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

use Symfony\Bundle\MakerBundle\Maker\MakeController;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\SkipOnWindows;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\UsesProfile;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\WindowsSmoke;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeController::class, profile: Profiles::MEGA)]
final class MakeControllerTest extends MakerTestCase
{
    #[LegacyCase('MakeControllerTest::it_generates_a_controller')]
    #[UsesProfile(Profiles::BASE)]
    public function testItGeneratesAController()
    {
        $this->app->runMaker()
            ->answer('Choose a name for your controller class', 'FooBar')
            ->answer('generate PHPUnit tests', 'no')
            ->run()
            ->assertCreatedExactly(['src/Controller/FooBarController.php']);

        $this->app->assertFileMatchesFixture('src/Controller/FooBarController.php', 'expected/FinalController.php');

        $this->app->runGeneratedTests('it_generates_a_controller.php', covers: ['src/Controller/FooBarController.php']);

        // lean guard: this only means anything because twig is really absent
        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            self::assertFalse(\Composer\InstalledVersions::isInstalled('symfony/twig-bundle'));
            PHP);
    }

    #[LegacyCase('MakeControllerTest::it_generates_a_controller-with-tests')]
    #[UsesProfile(Profiles::BASE)]
    public function testItGeneratesAControllerWithTests()
    {
        $this->app->runMaker()
            ->answer('Choose a name for your controller class', 'FooBar')
            ->answer('generate PHPUnit tests', 'y')
            ->run()
            ->assertCreated(
                'src/Controller/FooBarController.php',
                'tests/Controller/FooBarControllerTest.php',
            );

        // the contract demands running the exact generated test file
        $this->app->runGeneratedTestFile('tests/Controller/FooBarControllerTest.php', covers: [
            'src/Controller/FooBarController.php',
            'tests/Controller/FooBarControllerTest.php',
        ]);
    }

    #[LegacyCase('MakeControllerTest::it_generates_a_controller__no_input')]
    #[UsesProfile(Profiles::BASE)]
    public function testItGeneratesAControllerNoInput()
    {
        $this->app->runMaker()
            ->arguments('FooBar')
            ->answer('generate PHPUnit tests', 'no')
            ->run()
            ->assertCreatedExactly(['src/Controller/FooBarController.php']);

        $this->app->runGeneratedTests('it_generates_a_controller.php', covers: ['src/Controller/FooBarController.php']);
    }

    #[LegacyCase('MakeControllerTest::it_generates_a_controller_with_twig')]
    #[WindowsSmoke]
    public function testItGeneratesAControllerWithTwig()
    {
        $this->app->runMaker()
            ->answer('Choose a name for your controller class', 'FooTwig')
            ->answer('generate PHPUnit tests', 'no')
            ->run()
            ->assertCreated(
                'src/Controller/FooTwigController.php',
                'templates/foo_twig/index.html.twig',
            );

        $this->app->assertFileMatchesFixture('src/Controller/FooTwigController.php', 'expected/FinalControllerWithTemplate.php');

        $this->app->runGeneratedTests('it_generates_a_controller_with_twig.php', covers: [
            'src/Controller/FooTwigController.php',
            'templates/foo_twig/index.html.twig',
        ]);
    }

    #[LegacyCase('MakeControllerTest::it_generates_a_controller_with_twig__no_input')]
    public function testItGeneratesAControllerWithTwigNoInput()
    {
        $this->app->runMaker()
            ->arguments('FooTwig')
            ->answer('generate PHPUnit tests', 'no')
            ->run()
            ->assertCreated(
                'src/Controller/FooTwigController.php',
                'templates/foo_twig/index.html.twig',
            );

        $this->app->runGeneratedTests('it_generates_a_controller_with_twig.php', covers: [
            'src/Controller/FooTwigController.php',
            'templates/foo_twig/index.html.twig',
        ]);
    }

    #[LegacyCase('MakeControllerTest::it_generates_a_controller_with_twig_no_base_template')]
    public function testItGeneratesAControllerWithTwigNoBaseTemplate()
    {
        $this->app->deleteFile('templates/base.html.twig');

        $this->app->runMaker()
            ->answer('Choose a name for your controller class', 'FooTwig')
            ->answer('generate PHPUnit tests', 'no')
            ->run()
            ->assertCreated(
                'src/Controller/FooTwigController.php',
                'templates/foo_twig/index.html.twig',
            );

        $this->app->runGeneratedTests('it_generates_a_controller_with_twig.php', covers: [
            'src/Controller/FooTwigController.php',
            'templates/foo_twig/index.html.twig',
        ]);
    }

    #[LegacyCase('MakeControllerTest::it_generates_a_controller_with_without_template')]
    public function testItGeneratesAControllerWithoutTemplate()
    {
        $this->app->deleteFile('templates/base.html.twig');

        $this->app->runMaker()
            ->arguments('--no-template')
            ->answer('Choose a name for your controller class', 'FooNoTemplate')
            ->answer('generate PHPUnit tests', 'no')
            ->run()
            ->assertCreatedExactly(['src/Controller/FooNoTemplateController.php']);

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $client = static::createClient();
            $client->request('GET', '/foo/no/template');
            self::assertResponseIsSuccessful();
            PHP,
            covers: ['src/Controller/FooNoTemplateController.php'],
            webTestCase: true,
        );
    }

    #[LegacyCase('MakeControllerTest::it_generates_a_controller_in_sub_namespace')]
    #[UsesProfile(Profiles::BASE)]
    public function testItGeneratesAControllerInSubNamespace()
    {
        $this->app->runMaker()
            ->answer('Choose a name for your controller class', 'Admin\\FooBar')
            ->answer('generate PHPUnit tests', 'no')
            ->run()
            ->assertCreated('src/Controller/Admin/FooBarController.php');

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $client = static::createClient();
            $client->request('GET', '/admin/foo/bar');
            self::assertResponseIsSuccessful();
            PHP,
            covers: ['src/Controller/Admin/FooBarController.php'],
            webTestCase: true,
        );
    }

    #[LegacyCase('MakeControllerTest::it_generates_a_controller_in_sub_namespace__no_input')]
    #[UsesProfile(Profiles::BASE)]
    #[SkipOnWindows('argument backslash quoting is shell-specific')]
    public function testItGeneratesAControllerInSubNamespaceNoInput()
    {
        $this->app->runMaker()
            ->arguments('Admin\\\\FooBar')
            ->answer('generate PHPUnit tests', 'no')
            ->run()
            ->assertCreated('src/Controller/Admin/FooBarController.php');

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $client = static::createClient();
            $client->request('GET', '/admin/foo/bar');
            self::assertResponseIsSuccessful();
            PHP,
            covers: ['src/Controller/Admin/FooBarController.php'],
            webTestCase: true,
        );
    }

    #[LegacyCase('MakeControllerTest::it_generates_a_controller_in_sub_namespace_with_template')]
    #[WindowsSmoke]
    public function testItGeneratesAControllerInSubNamespaceWithTemplate()
    {
        $this->app->runMaker()
            ->answer('Choose a name for your controller class', 'Admin\\FooBar')
            ->answer('generate PHPUnit tests', 'no')
            ->run()
            ->assertCreated(
                'src/Controller/Admin/FooBarController.php',
                'templates/admin/foo_bar/index.html.twig',
            );

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $client = static::createClient();
            $client->request('GET', '/admin/foo/bar');
            self::assertResponseIsSuccessful();
            PHP,
            covers: [
                'src/Controller/Admin/FooBarController.php',
                'templates/admin/foo_bar/index.html.twig',
            ],
            webTestCase: true,
        );
    }

    #[LegacyCase('MakeControllerTest::it_generates_a_controller_with_full_custom_namespace')]
    public function testItGeneratesAControllerWithFullCustomNamespace()
    {
        $this->app->runMaker()
            ->answer('Choose a name for your controller class', '\\App\\Foo\\Bar\\CoolController')
            ->answer('generate PHPUnit tests', 'no')
            ->run()
            ->assertCreated(
                'src/Foo/Bar/CoolController.php',
                'templates/foo/bar/cool/index.html.twig',
            );

        $this->requestControllerOutsideDefaultRoutingDir('App\Foo\Bar\CoolController', [
            'src/Foo/Bar/CoolController.php',
            'templates/foo/bar/cool/index.html.twig',
        ]);
    }

    #[LegacyCase('MakeControllerTest::it_generates_a_controller_with_full_custom_namespace__no_input')]
    #[SkipOnWindows('argument backslash quoting is shell-specific')]
    public function testItGeneratesAControllerWithFullCustomNamespaceNoInput()
    {
        $this->app->runMaker()
            ->arguments('\\\\App\\\\Foo\\\\Bar\\\\CoolController')
            ->answer('generate PHPUnit tests', 'no')
            ->run()
            ->assertCreated(
                'src/Foo/Bar/CoolController.php',
                'templates/foo/bar/cool/index.html.twig',
            );

        $this->requestControllerOutsideDefaultRoutingDir('App\Foo\Bar\CoolController', [
            'src/Foo/Bar/CoolController.php',
            'templates/foo/bar/cool/index.html.twig',
        ]);
    }

    #[LegacyCase('MakeControllerTest::it_generates_a_controller_with_invoke')]
    public function testItGeneratesAControllerWithInvoke()
    {
        $this->app->runMaker()
            ->arguments('--invokable')
            ->answer('Choose a name for your controller class', 'FooInvokable')
            ->answer('generate PHPUnit tests', 'no')
            ->run()
            ->assertCreated(
                'src/Controller/FooInvokableController.php',
                'templates/foo_invokable.html.twig',
            );

        $this->app->runGeneratedTests('it_generates_an_invokable_controller.php', covers: [
            'src/Controller/FooInvokableController.php',
            'templates/foo_invokable.html.twig',
        ]);
    }

    /**
     * The default skeleton only scans src/Controller/ for route attributes, so
     * a controller generated elsewhere needs an explicit route: exactly what
     * a real user would add. The legacy test only checked file existence and
     * never noticed the class was unroutable as-is.
     *
     * @param list<string> $covers
     */
    private function requestControllerOutsideDefaultRoutingDir(string $controllerClass, array $covers): void
    {
        $this->app->writeYaml('config/routes/harness_test.yaml', [
            'harness_test_route' => [
                'path' => '/harness/test-route',
                'controller' => $controllerClass.'::index',
            ],
        ]);

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $client = static::createClient();
            $client->request('GET', '/harness/test-route');
            self::assertResponseIsSuccessful();
            PHP,
            covers: $covers,
            webTestCase: true,
        );
    }

    #[LegacyCase('MakeControllerTest::it_generates_a_controller_with_invoke_in_sub_namespace')]
    public function testItGeneratesAControllerWithInvokeInSubNamespace()
    {
        $this->app->runMaker()
            ->arguments('--invokable')
            ->answer('Choose a name for your controller class', 'Admin\\FooInvokable')
            ->answer('generate PHPUnit tests', 'no')
            ->run()
            ->assertCreated(
                'src/Controller/Admin/FooInvokableController.php',
                'templates/admin/foo_invokable.html.twig',
            );

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $client = static::createClient();
            $client->request('GET', '/admin/foo/invokable');
            self::assertResponseIsSuccessful();
            PHP,
            covers: [
                'src/Controller/Admin/FooInvokableController.php',
                'templates/admin/foo_invokable.html.twig',
            ],
            webTestCase: true,
        );
    }
}
