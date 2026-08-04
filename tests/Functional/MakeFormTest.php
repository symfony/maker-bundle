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

use Symfony\Bundle\MakerBundle\Maker\MakeForm;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\WindowsSmoke;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeForm::class, profile: Profiles::MEGA)]
final class MakeFormTest extends MakerTestCase
{
    #[LegacyCase('MakeFormTest::it_generates_basic_form')]
    public function testItGeneratesBasicForm()
    {
        $this->app->runMaker()
            ->answer('name of the form class', 'FooBar')
            ->acceptDefault('fully qualified model class name')
            ->run()
            ->assertCreatedExactly(['src/Form/FooBarType.php']);

        $this->app->runGeneratedTests('it_generates_basic_form.php', covers: ['src/Form/FooBarType.php']);
    }

    #[LegacyCase('MakeFormTest::it_generates_form_with_entity')]
    #[WindowsSmoke]
    public function testItGeneratesFormWithEntity()
    {
        $this->app->copyFixture('Property.php', 'src/Entity/Property.php');
        $this->app->copyFixture('SourFood.php', 'src/Entity/SourFood.php');

        $this->app->runMaker()
            ->answer('name of the form class', 'SourFoodType')
            ->answer('fully qualified model class name', 'SourFood')
            ->run()
            ->assertCreatedExactly(['src/Form/SourFoodType.php']);

        $this->app->runGeneratedTests('it_generates_form_with_entity.php', covers: ['src/Form/SourFoodType.php']);
    }

    #[LegacyCase('MakeFormTest::it_generates_form_with_non_entity_dto')]
    public function testItGeneratesFormWithNonEntityDto()
    {
        $this->app->copyFixture('TaskData.php', 'src/Form/Data/TaskData.php');

        $this->app->runMaker()
            ->answer('name of the form class', 'TaskType')
            ->answer('fully qualified model class name', '\\App\\Form\\Data\\TaskData')
            ->run()
            ->assertCreatedExactly(['src/Form/TaskType.php']);

        $this->app->runGeneratedTests('it_generates_form_with_non_entity_dto.php', covers: ['src/Form/TaskType.php']);
    }

    #[LegacyCase('MakeFormTest::it_generates_form_with_single_table_inheritance_entity')]
    public function testItGeneratesFormWithSingleTableInheritanceEntity()
    {
        $this->app->copyFixture('inheritance/Food.php', 'src/Entity/Food.php');
        $this->app->copyFixture('inheritance/SourFood.php', 'src/Entity/SourFood.php');

        $this->app->runMaker()
            ->answer('name of the form class', 'SourFoodType')
            ->answer('fully qualified model class name', 'SourFood')
            ->run()
            ->assertCreatedExactly(['src/Form/SourFoodType.php']);

        $this->app->runGeneratedTests('it_generates_form_with_single_table_inheritance_entity.php', covers: ['src/Form/SourFoodType.php']);
    }

    #[LegacyCase('MakeFormTest::it_generates_form_with_many_to_one_relation')]
    public function testItGeneratesFormWithManyToOneRelation()
    {
        $this->app->copyFixture('relation_one_to_many/Book.php', 'src/Entity/Book.php');
        $this->app->copyFixture('relation_one_to_many/Author.php', 'src/Entity/Author.php');

        $this->app->runMaker()
            ->answer('name of the form class', 'BookType')
            ->answer('fully qualified model class name', 'Book')
            ->run()
            ->assertCreatedExactly(['src/Form/BookType.php']);

        $this->app->runGeneratedTests('it_generates_form_with_many_to_one_relation.php', covers: ['src/Form/BookType.php']);
    }

    #[LegacyCase('MakeFormTest::it_generates_form_with_one_to_many_relation')]
    public function testItGeneratesFormWithOneToManyRelation()
    {
        $this->app->copyFixture('relation_one_to_many/Book.php', 'src/Entity/Book.php');
        $this->app->copyFixture('relation_one_to_many/Author.php', 'src/Entity/Author.php');

        $this->app->runMaker()
            ->answer('name of the form class', 'AuthorType')
            ->answer('fully qualified model class name', 'Author')
            ->run()
            ->assertCreatedExactly(['src/Form/AuthorType.php']);

        $this->app->runGeneratedTests('it_generates_form_with_one_to_many_relation.php', covers: ['src/Form/AuthorType.php']);
    }

    #[LegacyCase('MakeFormTest::it_generates_form_with_many_to_many_relation')]
    public function testItGeneratesFormWithManyToManyRelation()
    {
        $this->app->copyFixture('relation_many_to_many/Book.php', 'src/Entity/Book.php');
        $this->app->copyFixture('relation_many_to_many/Library.php', 'src/Entity/Library.php');

        $this->app->runMaker()
            ->answer('name of the form class', 'BookType')
            ->answer('fully qualified model class name', 'Book')
            ->run()
            ->assertCreatedExactly(['src/Form/BookType.php']);

        $this->app->runGeneratedTests('it_generates_form_with_many_to_many_relation.php', covers: ['src/Form/BookType.php']);
    }

    #[LegacyCase('MakeFormTest::it_generates_form_with_one_to_one_relation')]
    public function testItGeneratesFormWithOneToOneRelation()
    {
        $this->app->copyFixture('relation_one_to_one/Librarian.php', 'src/Entity/Librarian.php');
        $this->app->copyFixture('relation_one_to_one/Library.php', 'src/Entity/Library.php');

        $this->app->runMaker()
            ->answer('name of the form class', 'LibraryType')
            ->answer('fully qualified model class name', 'Library')
            ->run()
            ->assertCreatedExactly(['src/Form/LibraryType.php']);

        $this->app->runGeneratedTests('it_generates_form_with_one_to_one_relation.php', covers: ['src/Form/LibraryType.php']);
    }

    #[LegacyCase('MakeFormTest::it_generates_form_with_embeddable_entity')]
    public function testItGeneratesFormWithEmbeddableEntity()
    {
        $this->app->copyFixture('embeddable/Food.php', 'src/Entity/Food.php');
        $this->app->copyFixture('embeddable/Receipt.php', 'src/Entity/Receipt.php');

        $this->app->runMaker()
            ->answer('name of the form class', 'FoodType')
            ->answer('fully qualified model class name', 'Food')
            ->run()
            ->assertCreatedExactly(['src/Form/FoodType.php']);

        $this->app->runGeneratedTests('it_generates_form_with_embeddable_entity.php', covers: ['src/Form/FoodType.php']);
    }
}
