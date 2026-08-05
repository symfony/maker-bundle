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

use Symfony\Bundle\MakerBundle\Maker\MakeCrud;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\UsesProfile;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\WindowsSmoke;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeCrud::class, profile: Profiles::MEGA)]
final class MakeCrudTest extends MakerTestCase
{
    private const SWEET_FOOD_ARTIFACTS = [
        'src/Controller/SweetFoodController.php',
        'src/Form/SweetFoodType.php',
        'templates/sweet_food/index.html.twig',
        'templates/sweet_food/new.html.twig',
        'templates/sweet_food/edit.html.twig',
        'templates/sweet_food/show.html.twig',
        'templates/sweet_food/_form.html.twig',
        'templates/sweet_food/_delete_form.html.twig',
    ];

    #[LegacyCase('MakeCrudTest::it_generates_basic_crud')]
    #[WindowsSmoke]
    public function testItGeneratesBasicCrud()
    {
        $this->app->copyFixture('SweetFood.php', 'src/Entity/SweetFood.php');

        $this->app->runMaker()
            ->answer('class name of the entity to create CRUD', 'SweetFood')
            ->acceptDefault('Choose a name for your controller class')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertOutputContains('src/Controller/SweetFoodController.php')
            ->assertOutputContains('src/Form/SweetFoodType.php')
            ->assertCreated(...self::SWEET_FOOD_ARTIFACTS);

        $this->app->prepareDatabase();
        $this->app->runGeneratedTests('it_generates_basic_crud.php', covers: self::SWEET_FOOD_ARTIFACTS);
    }

    #[LegacyCase('MakeCrudTest::it_generates_crud_with_custom_controller')]
    public function testItGeneratesCrudWithCustomController()
    {
        $this->app->copyFixture('SweetFood.php', 'src/Entity/SweetFood.php');

        $artifacts = [
            'src/Controller/SweetFoodAdminController.php',
            'src/Form/SweetFoodType.php',
            'templates/sweet_food_admin/index.html.twig',
            'templates/sweet_food_admin/new.html.twig',
            'templates/sweet_food_admin/edit.html.twig',
            'templates/sweet_food_admin/show.html.twig',
            'templates/sweet_food_admin/_form.html.twig',
            'templates/sweet_food_admin/_delete_form.html.twig',
            // the generated test is named after the entity, not the controller
            'tests/Controller/SweetFoodControllerTest.php',
        ];

        $this->app->runMaker()
            ->answer('class name of the entity to create CRUD', 'SweetFood')
            ->answer('Choose a name for your controller class', 'SweetFoodAdminController')
            ->answer('generate PHPUnit tests', 'y')
            ->run()
            ->assertOutputContains('src/Controller/SweetFoodAdminController.php')
            ->assertOutputContains('src/Form/SweetFoodType.php')
            ->assertCreated(...$artifacts);

        $this->app->prepareDatabase();
        $this->app->runGeneratedTests('it_generates_crud_with_custom_controller.php', covers: $artifacts);
        // the contract demands running the exact generated test file too
        $this->app->runGeneratedTestFile('tests/Controller/SweetFoodControllerTest.php');
    }

    #[LegacyCase('MakeCrudTest::it_generates_crud_with_tests')]
    public function testItGeneratesCrudWithTests()
    {
        $this->app->copyFixture('SweetFood.php', 'src/Entity/SweetFood.php');

        $this->app->runMaker()
            ->answer('class name of the entity to create CRUD', 'SweetFood')
            ->acceptDefault('Choose a name for your controller class')
            ->answer('generate PHPUnit tests', 'y')
            ->run()
            ->assertOutputContains('tests/Controller/SweetFoodControllerTest.php')
            ->assertCreated('tests/Controller/SweetFoodControllerTest.php', ...self::SWEET_FOOD_ARTIFACTS);

        $this->app->prepareDatabase();
        $this->app->runGeneratedTests('it_generates_basic_crud.php', covers: self::SWEET_FOOD_ARTIFACTS);
        $this->app->runGeneratedTestFile('tests/Controller/SweetFoodControllerTest.php', covers: ['tests/Controller/SweetFoodControllerTest.php']);
    }

    #[LegacyCase('MakeCrudTest::it_generates_correct_class_methods')]
    public function testItGeneratesCorrectClassMethods()
    {
        $this->app->copyFixture('Foo.php', 'src/Entity/Foo.php');

        $artifacts = [
            'src/Controller/FooController.php',
            'src/Form/FooType.php',
            'templates/foo/index.html.twig',
            'templates/foo/new.html.twig',
            'templates/foo/edit.html.twig',
            'templates/foo/show.html.twig',
            'templates/foo/_form.html.twig',
            'templates/foo/_delete_form.html.twig',
            'tests/Controller/FooControllerTest.php',
        ];

        $this->app->runMaker()
            ->answer('class name of the entity to create CRUD', 'Foo')
            ->acceptDefault('Choose a name for your controller class')
            ->answer('generate PHPUnit tests', 'y')
            ->run()
            ->assertCreated(...$artifacts);

        // asserts the generated test uses camelCase accessors (getFooBar())
        $this->app->runGeneratedTests('it_generates_correct_class_methods.php');

        $this->app->prepareDatabase();
        $this->app->runGeneratedTestFile('tests/Controller/FooControllerTest.php', covers: $artifacts);
    }

    #[LegacyCase('MakeCrudTest::it_generates_crud_custom_repository_with_test')]
    public function testItGeneratesCrudCustomRepositoryWithTest()
    {
        $this->app->copyFixture('SweetFoodCustomRepository.php', 'src/Entity/SweetFood.php');
        $this->app->copyFixture('SweetFoodRepository.php', 'src/Repository/SweetFoodRepository.php');

        $this->app->runMaker()
            ->answer('class name of the entity to create CRUD', 'SweetFood')
            ->acceptDefault('Choose a name for your controller class')
            ->answer('generate PHPUnit tests', 'y')
            ->run()
            ->assertCreated('tests/Controller/SweetFoodControllerTest.php', ...self::SWEET_FOOD_ARTIFACTS);

        $this->app->prepareDatabase();
        $this->app->runGeneratedTests('it_generates_basic_crud.php', covers: self::SWEET_FOOD_ARTIFACTS);
        $this->app->runGeneratedTestFile('tests/Controller/SweetFoodControllerTest.php', covers: ['tests/Controller/SweetFoodControllerTest.php']);
    }

    #[LegacyCase('MakeCrudTest::it_generates_crud_using_custom_repository')]
    public function testItGeneratesCrudUsingCustomRepository()
    {
        $this->app->copyFixture('SweetFoodCustomRepository.php', 'src/Entity/SweetFood.php');
        $this->app->copyFixture('SweetFoodRepository.php', 'src/Repository/SweetFoodRepository.php');

        $this->app->runMaker()
            ->answer('class name of the entity to create CRUD', 'SweetFood')
            ->acceptDefault('Choose a name for your controller class')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertCreated(...self::SWEET_FOOD_ARTIFACTS);

        $this->app->assertFileMatchesFixture('src/Controller/SweetFoodController.php', 'expected/WithCustomRepository.php');

        $this->app->prepareDatabase();
        $this->app->runGeneratedTests('it_generates_basic_crud.php', covers: self::SWEET_FOOD_ARTIFACTS);
    }

    #[LegacyCase('MakeCrudTest::it_generates_crud_with_custom_root_namespace')]
    #[UsesProfile(Profiles::MEGA_CUSTOM_NAMESPACE)]
    public function testItGeneratesCrudWithCustomRootNamespace()
    {
        $this->app->copyFixture('SweetFood-custom-namespace.php', 'src/Entity/SweetFood.php');

        $this->app->runMaker()
            ->answer('class name of the entity to create CRUD', 'SweetFood')
            ->acceptDefault('Choose a name for your controller class')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertOutputContains('src/Controller/SweetFoodController.php')
            ->assertOutputContains('src/Form/SweetFoodType.php')
            ->assertCreated(...self::SWEET_FOOD_ARTIFACTS);

        $this->app->prepareDatabase();
        $this->app->runGeneratedTests('it_generates_crud_with_custom_root_namespace.php', covers: self::SWEET_FOOD_ARTIFACTS);
    }

    #[LegacyCase('MakeCrudTest::it_generates_crud_with_no_base_template')]
    public function testItGeneratesCrudWithNoBaseTemplate()
    {
        $this->app->copyFixture('SweetFood.php', 'src/Entity/SweetFood.php');
        $this->app->deleteFile('templates/base.html.twig');

        $this->app->runMaker()
            ->answer('class name of the entity to create CRUD', 'SweetFood')
            ->acceptDefault('Choose a name for your controller class')
            ->answer('generate PHPUnit tests', 'n')
            ->run()
            ->assertCreated(...self::SWEET_FOOD_ARTIFACTS);

        $this->app->prepareDatabase();
        $this->app->runGeneratedTests('it_generates_basic_crud.php', covers: self::SWEET_FOOD_ARTIFACTS);
    }
}
