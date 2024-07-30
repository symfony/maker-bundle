<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Util;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\FieldMapping;
use PhpParser\Builder\Param;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Doctrine\RelationManyToMany;
use Symfony\Bundle\MakerBundle\Doctrine\RelationManyToOne;
use Symfony\Bundle\MakerBundle\Doctrine\RelationOneToMany;
use Symfony\Bundle\MakerBundle\Doctrine\RelationOneToOne;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassProperty;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Component\Security\Core\User\UserInterface;

class ClassSourceManipulatorTest extends TestCase
{
    /**
     * @dataProvider getAddPropertyTests
     */
    public function testAddProperty(string $sourceFilename, $propertyName, array $commentLines, $expectedSourceFilename): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_property/'.$expectedSourceFilename);

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addProperty(name: $propertyName, comments: $commentLines);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function getAddPropertyTests(): \Generator
    {
        yield 'normal_property_add' => [
            'User_simple.php',
            'fooProp',
            [],
            'User_simple.php',
        ];

        yield 'with_no_properties_and_comment' => [
            'User_no_props.php',
            'fooProp',
            [
                '@var string',
                '@internal',
            ],
            'User_no_props.php',
        ];

        yield 'no_properties_and_constants' => [
            'User_no_props_constants.php',
            'fooProp',
            [],
            'User_no_props_constants.php',
        ];

        yield 'property_empty_class' => [
            'User_empty.php',
            'fooProp',
            [],
            'User_empty.php',
        ];
    }

    /**
     * @dataProvider getAddGetterTests
     */
    public function testAddGetter(string $sourceFilename, string $propertyName, string $type, array $commentLines, $expectedSourceFilename): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_getter/'.$expectedSourceFilename);

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addGetter($propertyName, $type, true, $commentLines);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function getAddGetterTests(): \Generator
    {
        yield 'normal_getter_add' => [
            'User_simple.php',
            'fooProp',
            'string',
            [],
            'User_simple.php',
        ];

        yield 'normal_getter_add_bool' => [
            'User_simple.php',
            'fooProp',
            'bool',
            [],
            'User_simple_bool.php',
        ];

        yield 'getter_bool_begins_with_is' => [
            'User_simple.php',
            'isFooProp',
            'bool',
            [],
            'User_bool_begins_with_is.php',
        ];

        yield 'getter_bool_begins_with_has' => [
            'User_simple.php',
            'hasFooProp',
            'bool',
            [],
            'User_bool_begins_with_has.php',
        ];

        yield 'getter_no_props_comments' => [
            'User_no_props.php',
            'fooProp',
            'string',
            [
                '@return string',
                '@internal',
            ],
            'User_no_props.php',
        ];

        yield 'getter_empty_class' => [
            'User_empty.php',
            'fooProp',
            'string',
            [],
            'User_empty.php',
        ];
    }

    /**
     * @dataProvider getAddSetterTests
     */
    public function testAddSetter(string $sourceFilename, string $propertyName, ?string $type, bool $isNullable, array $commentLines, $expectedSourceFilename): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_setter/'.$expectedSourceFilename);

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addSetter($propertyName, $type, $isNullable, $commentLines);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function getAddSetterTests(): \Generator
    {
        yield 'normal_setter_add' => [
            'User_simple.php',
            'fooProp',
            'string',
            false,
            [],
            'User_simple.php',
        ];

        yield 'setter_no_props_comments' => [
            'User_no_props.php',
            'fooProp',
            'string',
            true,
            [
                '@param string $fooProp',
                '@internal',
            ],
            'User_no_props.php',
        ];

        yield 'setter_empty_class' => [
            'User_empty.php',
            'fooProp',
            'string',
            false,
            [],
            'User_empty.php',
        ];

        yield 'setter_null_type' => [
            'User_simple.php',
            'fooProp',
            null,
            false,
            [],
            'User_simple_null_type.php',
        ];

        yield 'setter_bool_begins_with_is' => [
            'User_simple.php',
            'isFooProp',
            'bool',
            false,
            [],
            'User_bool_begins_with_is.php',
        ];
    }

    /**
     * @dataProvider getAttributeClassTests
     */
    public function testAddAttributeToClass(string $sourceFilename, string $expectedSourceFilename, string $attributeClass, array $attributeOptions, ?string $attributePrefix = null): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_class_attribute/'.$expectedSourceFilename);
        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addAttributeToClass($attributeClass, $attributeOptions, $attributePrefix);

        self::assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function getAttributeClassTests(): \Generator
    {
        yield 'Empty class' => [
            'User_empty.php',
            'User_empty.php',
            Entity::class,
            [],
        ];

        yield 'Class already has attributes' => [
            'User_simple.php',
            'User_simple.php',
            Column::class,
            ['message' => 'We use this attribute for class level tests so we dont have to add additional test dependencies.'],
        ];
    }

    /**
     * @dataProvider getAddEntityFieldTests
     */
    public function testAddEntityField(string $sourceFilename, ClassProperty $propertyModel, $expectedSourceFilename): void
    {
        $sourcePath = __DIR__.'/fixtures/source';
        $expectedPath = __DIR__.'/fixtures/add_entity_field';

        $this->runAddEntityFieldTests(
            file_get_contents(\sprintf('%s/%s', $sourcePath, $sourceFilename)),
            $propertyModel,
            file_get_contents(\sprintf('%s/%s', $expectedPath, $expectedSourceFilename))
        );
    }

    private function runAddEntityFieldTests(string $source, ClassProperty $fieldOptions, string $expected): void
    {
        $manipulator = new ClassSourceManipulator($source, false);
        $manipulator->addEntityField($fieldOptions);

        $this->assertSame($expected, $manipulator->getSourceCode());
    }

    public function getAddEntityFieldTests(): \Generator
    {
        /** @legacy - Remove when Doctrine/ORM 2.x is no longer supported. */
        $isLegacy = !class_exists(FieldMapping::class);

        yield 'entity_normal_add' => [
            'User_simple.php',
            new ClassProperty(propertyName: 'fooProp', type: 'string', length: 255, nullable: false, options: ['comment' => 'new field']),
            'User_simple.php',
        ];

        yield 'entity_add_datetime' => [
            'User_simple.php',
            new ClassProperty(propertyName: 'createdAt', type: 'datetime', nullable: true),
            'User_simple_datetime.php',
        ];

        yield 'entity_field_property_already_exists' => [
            'User_some_props.php',
            new ClassProperty(propertyName: 'firstName', type: 'string', length: 255, nullable: false),
            'User_simple_prop_already_exists.php',
        ];

        yield 'entity_field_property_zero' => [
            'User_simple.php',
            new ClassProperty(propertyName: 'decimal', type: 'decimal', precision: 6, scale: 0),
            'User_simple_prop_zero.php',
        ];

        yield 'entity_add_object' => [
            'User_simple.php',
            new ClassProperty(propertyName: 'someObject', type: 'object'),
            $isLegacy ? 'legacy/User_simple_object.php' : 'User_simple_object.php',
        ];

        yield 'entity_add_uuid' => [
            'User_simple.php',
            new ClassProperty(propertyName: 'uuid', type: 'uuid'),
            'User_simple_uuid.php',
        ];

        yield 'entity_add_ulid' => [
            'User_simple.php',
            new ClassProperty(propertyName: 'ulid', type: 'ulid'),
            'User_simple_ulid.php',
        ];
    }

    /**
     * @dataProvider getAddManyToOneRelationTests
     */
    public function testAddManyToOneRelation(string $sourceFilename, $expectedSourceFilename, RelationManyToOne $manyToOne): void
    {
        $sourcePath = __DIR__.'/fixtures/source';
        $expectedPath = __DIR__.'/fixtures/add_many_to_one_relation';

        $this->runAddManyToOneRelationTests(
            file_get_contents(\sprintf('%s/%s', $sourcePath, $sourceFilename)),
            file_get_contents(\sprintf('%s/%s', $expectedPath, $expectedSourceFilename)),
            $manyToOne
        );
    }

    public function runAddManyToOneRelationTests(string $source, string $expected, RelationManyToOne $manyToOne): void
    {
        $manipulator = new ClassSourceManipulator($source, false);
        $manipulator->addManyToOneRelation($manyToOne);

        $this->assertSame($expected, $manipulator->getSourceCode());
    }

    public function getAddManyToOneRelationTests(): \Generator
    {
        yield 'many_to_one_not_nullable' => [
            'User_simple.php',
            'User_simple_not_nullable.php',
            new RelationManyToOne(
                propertyName: 'category',
                targetClassName: \App\Entity\Category::class,
                targetPropertyName: 'foods',
                isOwning: true,
            ),
        ];

        yield 'many_to_one_nullable' => [
            'User_simple.php',
            'User_simple_nullable.php',
            new RelationManyToOne(
                propertyName: 'category',
                targetClassName: \App\Entity\Category::class,
                targetPropertyName: 'foods',
                isOwning: true,
                isNullable: true,
            ),
        ];

        yield 'many_to_one_other_namespace' => [
            'User_simple.php',
            'User_simple_other_namespace.php',
            new RelationManyToOne(
                propertyName: 'category',
                targetClassName: \Foo\Entity\Category::class,
                targetPropertyName: 'foods',
                isOwning: true,
                isNullable: true,
            ),
        ];

        yield 'many_to_one_empty_other_namespace' => [
            'User_empty.php',
            'User_empty_other_namespace.php',
            new RelationManyToOne(
                propertyName: 'category',
                targetClassName: \Foo\Entity\Category::class,
                targetPropertyName: 'foods',
                isOwning: true,
                isNullable: true,
            ),
        ];

        yield 'many_to_one_same_and_other_namespaces' => [
            'User_with_relation.php',
            'User_with_relation_same_and_other_namespaces.php',
            new RelationManyToOne(
                propertyName: 'subCategory',
                targetClassName: \App\Entity\SubDirectory\Category::class,
                targetPropertyName: 'foods',
                isOwning: true,
                isNullable: true,
            ),
        ];

        yield 'many_to_one_no_inverse' => [
            'User_simple.php',
            'User_simple_no_inverse.php',
            new RelationManyToOne(
                propertyName: 'category',
                targetClassName: \App\Entity\Category::class,
                targetPropertyName: 'foods',
                mapInverseRelation: false,
                isOwning: true,
                isNullable: true,
            ),
        ];
    }

    /**
     * @dataProvider getAddOneToManyRelationTests
     */
    public function testAddOneToManyRelation(string $sourceFilename, string $expectedSourceFilename, RelationOneToMany $oneToMany): void
    {
        $sourcePath = __DIR__.'/fixtures/source';
        $expectedPath = __DIR__.'/fixtures/add_one_to_many_relation';

        /* @legacy - Remove when Doctrine/ORM 2.x is no longer supported. */
        if (!class_exists(FieldMapping::class)) {
            $expectedPath .= '/legacy';
        }

        $this->runAddOneToManyRelationTests(
            file_get_contents(\sprintf('%s/%s', $sourcePath, $sourceFilename)),
            file_get_contents(\sprintf('%s/%s', $expectedPath, $expectedSourceFilename)),
            $oneToMany
        );
    }

    private function runAddOneToManyRelationTests(string $source, string $expected, RelationOneToMany $oneToMany): void
    {
        $manipulator = new ClassSourceManipulator($source, false);
        $manipulator->addOneToManyRelation($oneToMany);

        $this->assertSame($expected, $manipulator->getSourceCode());
    }

    public function getAddOneToManyRelationTests(): \Generator
    {
        yield 'one_to_many_simple' => [
            'User_simple.php',
            'User_simple.php',
            new RelationOneToMany(
                propertyName: 'avatarPhotos',
                targetClassName: \App\Entity\UserAvatarPhoto::class,
                targetPropertyName: 'user',
            ),
        ];

        // interesting also because the source file has its
        // use statements out of alphabetical order
        yield 'one_to_many_simple_no_duplicate_use' => [
            'User_with_use_statements.php',
            'User_with_use_statements.php',
            new RelationOneToMany(
                propertyName: 'avatarPhotos',
                targetClassName: \App\Entity\UserAvatarPhoto::class,
                targetPropertyName: 'user',
            ),
        ];

        yield 'one_to_many_orphan_removal' => [
            'User_simple.php',
            'User_simple_orphan_removal.php',
            new RelationOneToMany(
                propertyName: 'avatarPhotos',
                targetClassName: \App\Entity\UserAvatarPhoto::class,
                targetPropertyName: 'user',
                orphanRemoval: true,
            ),
        ];

        // todo test existing constructor
    }

    /**
     * @dataProvider getAddManyToManyRelationTests
     */
    public function testAddManyToManyRelation(string $sourceFilename, $expectedSourceFilename, RelationManyToMany $manyToMany): void
    {
        $sourcePath = __DIR__.'/fixtures/source';
        $expectedPath = __DIR__.'/fixtures/add_many_to_many_relation';

        $this->runAddManyToManyRelationTest(
            file_get_contents(\sprintf('%s/%s', $sourcePath, $sourceFilename)),
            file_get_contents(\sprintf('%s/%s', $expectedPath, $expectedSourceFilename)),
            $manyToMany
        );
    }

    private function runAddManyToManyRelationTest(string $source, string $expected, RelationManyToMany $manyToMany): void
    {
        $manipulator = new ClassSourceManipulator($source, false);
        $manipulator->addManyToManyRelation($manyToMany);

        $this->assertSame($expected, $manipulator->getSourceCode());
    }

    public function getAddManyToManyRelationTests(): \Generator
    {
        yield 'many_to_many_owning' => [
            'User_simple.php',
            'User_simple_owning.php',
            new RelationManyToMany(
                propertyName: 'recipes',
                targetClassName: \App\Entity\Recipe::class,
                targetPropertyName: 'foods',
                isOwning: true,
            ),
        ];

        yield 'many_to_many_inverse' => [
            'User_simple.php',
            'User_simple_inverse.php',
            new RelationManyToMany(
                propertyName: 'recipes',
                targetClassName: \App\Entity\Recipe::class,
                targetPropertyName: 'foods',
            ),
        ];

        yield 'many_to_many_owning_inverse' => [
            'User_simple.php',
            'User_simple_no_inverse.php',
            new RelationManyToMany(
                propertyName: 'recipes',
                targetClassName: \App\Entity\Recipe::class,
                targetPropertyName: 'foods',
                mapInverseRelation: false,
                isOwning: true,
            ),
        ];
    }

    /**
     * @dataProvider getAddOneToOneRelationTests
     */
    public function testAddOneToOneRelation(string $sourceFilename, $expectedSourceFilename, RelationOneToOne $oneToOne): void
    {
        $sourcePath = __DIR__.'/fixtures/source';
        $expectedPath = __DIR__.'/fixtures/add_one_to_one_relation';

        $this->runAddOneToOneRelation(
            file_get_contents(\sprintf('%s/%s', $sourcePath, $sourceFilename)),
            file_get_contents(\sprintf('%s/%s', $expectedPath, $expectedSourceFilename)),
            $oneToOne
        );
    }

    private function runAddOneToOneRelation(string $source, string $expected, RelationOneToOne $oneToOne): void
    {
        $manipulator = new ClassSourceManipulator($source, false);
        $manipulator->addOneToOneRelation($oneToOne);

        $this->assertSame($expected, $manipulator->getSourceCode());
    }

    public function getAddOneToOneRelationTests(): \Generator
    {
        /** @legacy - Remove when Doctrine/ORM 2.x is no longer supported. */
        $isLegacy = !class_exists(FieldMapping::class);

        yield 'one_to_one_owning' => [
            'User_simple.php',
            'User_simple_owning.php',
            new RelationOneToOne(
                propertyName: 'userProfile',
                targetClassName: \App\Entity\UserProfile::class,
                targetPropertyName: 'user',
                isOwning: true,
                isNullable: true,
            ),
        ];

        // a relationship to yourself - return type is self
        yield 'one_to_one_owning_self' => [
            'User_simple.php',
            $isLegacy ? 'legacy/User_simple_self.php' : 'User_simple_self.php',
            new RelationOneToOne(
                propertyName: 'embeddedUser',
                targetClassName: \App\Entity\User::class,
                targetPropertyName: 'user',
                isOwning: true,
                isNullable: true,
            ),
        ];

        yield 'one_to_one_inverse' => [
            'UserProfile_simple.php',
            'UserProfile_simple_inverse.php',
            new RelationOneToOne(
                propertyName: 'user',
                targetClassName: \App\Entity\User::class,
                targetPropertyName: 'userProfile',
                isNullable: true,
            ),
        ];

        yield 'one_to_one_inverse_not_nullable' => [
            'UserProfile_simple.php',
            'UserProfile_simple_inverse_not_nullable.php',
            new RelationOneToOne(
                propertyName: 'user',
                targetClassName: \App\Entity\User::class,
                targetPropertyName: 'userProfile',
            ),
        ];

        yield 'one_to_one_no_inverse' => [
            'User_simple.php',
            'User_simple_no_inverse.php',
            new RelationOneToOne(
                propertyName: 'userProfile',
                targetClassName: \App\Entity\UserProfile::class,
                mapInverseRelation: false,
                isOwning: true,
                isNullable: true,
            ),
        ];

        yield 'one_to_one_no_inverse_not_nullable' => [
            'User_simple.php',
            'User_simple_no_inverse_not_nullable.php',
            new RelationOneToOne(
                propertyName: 'userProfile',
                targetClassName: \App\Entity\UserProfile::class,
                mapInverseRelation: false,
                isOwning: true,
            ),
        ];

        yield 'avoid_duplicate_use_statement' => [
            'User_with_use_statements.php',
            'User_with_use_statements_avoid_duplicate_use.php',
            new RelationOneToOne(
                propertyName: 'userProfile',
                targetClassName: \App\OtherEntity\UserProfile::class,
                targetPropertyName: 'user',
                isOwning: true,
                isNullable: true,
            ),
        ];

        yield 'avoid_duplicate_use_statement_with_alias' => [
            'User_with_use_statements.php',
            'User_with_use_statements_avoid_duplicate_use_alias.php',
            new RelationOneToOne(
                propertyName: 'category',
                targetClassName: \App\OtherEntity\Category::class,
                targetPropertyName: 'user',
                isOwning: true,
                isNullable: true,
            ),
        ];
    }

    public function testAddInterface(): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_simple.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/implements_interface/User_simple.php');

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addInterface(UserInterface::class);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddInterfaceToClassWithOtherInterface(): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_simple_with_interface.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/implements_interface/User_simple_with_interface.php');

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addInterface(UserInterface::class);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddMethodBuilder(): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_empty.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_method/UserEmpty_with_newMethod.php');

        $manipulator = new ClassSourceManipulator($source);

        $methodBuilder = $manipulator->createMethodBuilder('testAddNewMethod', 'string', true, ['test comment on public method']);

        $manipulator->addMethodBuilder(
            $methodBuilder,
            [
                (new Param('someParam'))->setType('string')->getNode(),
            ], <<<'CODE'
                <?php
                $this->someParam = $someParam;
                CODE
        );

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddMethodWithBody(): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/EmptyController.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_method/Controller_with_action.php');

        $manipulator = new ClassSourceManipulator($source);

        $methodBuilder = $manipulator->createMethodBuilder('action', 'JsonResponse', false, ['@Route("/action", name="app_action")']);
        $methodBuilder->addParam(
            (new Param('param'))->setType('string')
        );
        $manipulator->addMethodBody($methodBuilder,
            <<<'CODE'
                <?php
                return new JsonResponse(['param' => $param]);
                CODE
        );
        $manipulator->addMethodBuilder($methodBuilder);
        $manipulator->addUseStatementIfNecessary('Symfony\\Component\\HttpFoundation\\JsonResponse');
        $manipulator->addUseStatementIfNecessary('Symfony\\Component\\Routing\\Attribute\\Route');

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddTraitInEmptyClass(): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_empty.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_trait/User_with_only_trait.php');

        $manipulator = new ClassSourceManipulator($source);

        $manipulator->addTrait('App\TestTrait');

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddTraitWithProperty(): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_simple.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_trait/User_with_prop_trait.php');

        $manipulator = new ClassSourceManipulator($source);

        $manipulator->addTrait('App\TestTrait');

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddTraitWithConstant(): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_with_const.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_trait/User_with_const_trait.php');

        $manipulator = new ClassSourceManipulator($source);

        $manipulator->addTrait('App\TestTrait');

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddTraitWithTrait(): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_with_trait.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_trait/User_with_trait_trait.php');

        $manipulator = new ClassSourceManipulator($source);

        $manipulator->addTrait('App\TestTrait');

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddTraitAlReadyExists(): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/add_trait/User_with_trait_trait.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_trait/User_with_trait_trait.php');

        $manipulator = new ClassSourceManipulator($source);

        $manipulator->addTrait('App\TraitAlreadyHere');

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddConstructor(): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_empty.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_constructor/UserEmpty_with_constructor.php');

        $manipulator = new ClassSourceManipulator($source);

        $manipulator->addConstructor([
            (new Param('someObjectParam'))->setType('object')->getNode(),
            (new Param('someStringParam'))->setType('string')->getNode(),
        ], <<<'CODE'
            <?php
            $this->someObjectParam = $someObjectParam;
            $this->someMethod($someStringParam);
            CODE
        );

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddConstructorInClassContainsPropsAndMethods(): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_simple.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_constructor/UserSimple_with_constructor.php');

        $manipulator = new ClassSourceManipulator($source);

        $manipulator->addConstructor([
            (new Param('someObjectParam'))->setType('object')->getNode(),
            (new Param('someStringParam'))->setType('string')->getNode(),
        ], <<<'CODE'
            <?php
            $this->someObjectParam = $someObjectParam;
            $this->someMethod($someStringParam);
            CODE
        );

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddConstructorInClassContainsOnlyConstants(): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_with_const.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_constructor/User_with_constructor_constante.php');

        $manipulator = new ClassSourceManipulator($source);

        $manipulator->addConstructor([
            (new Param('someObjectParam'))->setType('object')->getNode(),
            (new Param('someStringParam'))->setType('string')->getNode(),
        ], <<<'CODE'
            <?php
            $this->someObjectParam = $someObjectParam;
            $this->someMethod($someStringParam);
            CODE
        );

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddConstructorInClassContainsConstructor(): void
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_with_constructor.php');

        $manipulator = new ClassSourceManipulator($source);

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Constructor already exists');

        $manipulator->addConstructor([
            (new Param('someObjectParam'))->setType('object')->getNode(),
            (new Param('someStringParam'))->setType('string')->getNode(),
        ], <<<'CODE'
            <?php
            $this->someObjectParam = $someObjectParam;
            $this->someMethod($someStringParam);
            CODE
        );
    }
}
