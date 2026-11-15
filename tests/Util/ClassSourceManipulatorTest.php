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
use PHPUnit\Framework\Attributes\DataProvider;
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
    #[DataProvider('getAddPropertyTests')]
    public function testAddProperty(string $sourceFilename, $propertyName, array $commentLines, $expectedSourceFilename)
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_property/'.$expectedSourceFilename);

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addProperty(name: $propertyName, comments: $commentLines);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public static function getAddPropertyTests(): \Generator
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
    #[DataProvider('getAddGetterTests')]
    public function testAddGetter(string $sourceFilename, string $propertyName, string $type, array $commentLines, $expectedSourceFilename)
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_getter/'.$expectedSourceFilename);

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addGetter($propertyName, $type, true, $commentLines);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public static function getAddGetterTests(): \Generator
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
    #[DataProvider('getAddSetterTests')]
    public function testAddSetter(string $sourceFilename, string $propertyName, ?string $type, bool $isNullable, array $commentLines, $expectedSourceFilename)
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_setter/'.$expectedSourceFilename);

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addSetter($propertyName, $type, $isNullable, $commentLines);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public static function getAddSetterTests(): \Generator
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
    #[DataProvider('getAttributeClassTests')]
    public function testAddAttributeToClass(string $sourceFilename, string $expectedSourceFilename, string $attributeClass, array $attributeOptions, ?string $attributePrefix = null)
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_class_attribute/'.$expectedSourceFilename);
        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addAttributeToClass($attributeClass, $attributeOptions, $attributePrefix);

        self::assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public static function getAttributeClassTests(): \Generator
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
    #[DataProvider('getAddEntityFieldTests')]
    public function testAddEntityField(string $sourceFilename, ClassProperty $propertyModel, $expectedSourceFilename)
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

    public static function getAddEntityFieldTests(): \Generator
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

        yield 'entity_promoted_add' => [
            'User_simple.php',
            new ClassProperty(propertyName: 'fooProp', type: 'string', length: 255, nullable: false, options: ['comment' => 'new field'], promoted: true),
            'User_simple_promoted.php',
        ];

        // a nullable field defaults to null: its parameter is optional
        yield 'entity_promoted_add_nullable' => [
            'User_simple.php',
            new ClassProperty(propertyName: 'createdAt', type: 'datetime', nullable: true, promoted: true),
            'User_simple_promoted_nullable.php',
        ];

        // and so is the one of an array, which defaults to an empty one
        yield 'entity_promoted_add_array' => [
            'User_simple.php',
            new ClassProperty(propertyName: 'roles', type: 'json', promoted: true),
            'User_simple_promoted_array.php',
        ];

        yield 'entity_promoted_add_with_comments' => [
            'User_simple.php',
            new ClassProperty(propertyName: 'password', type: 'string', comments: ['@var string The hashed password'], promoted: true),
            'User_simple_promoted_comments.php',
        ];
    }

    public function testAddEntityFieldThatIsAlreadyPromoted()
    {
        $manipulator = new ClassSourceManipulator(file_get_contents(__DIR__.'/fixtures/source/User_promoted_props.php'));

        // the properties are not added again, and their accessors follow the types they are promoted with
        $manipulator->addEntityField(new ClassProperty(propertyName: 'firstName', type: 'string', length: 255));
        $manipulator->addEntityField(new ClassProperty(propertyName: 'biography', type: 'text', nullable: true));

        $this->assertSame(
            file_get_contents(__DIR__.'/fixtures/add_entity_field/User_promoted_props.php'),
            $manipulator->getSourceCode()
        );
    }

    /**
     * @dataProvider getAddPromotedEntityFieldToConstructorTests
     */
    #[DataProvider('getAddPromotedEntityFieldToConstructorTests')]
    public function testAddPromotedEntityFieldToConstructor(string $sourceFilename, string $propertyName, string $expectedSourceFilename, bool $nullable = false)
    {
        $manipulator = new ClassSourceManipulator(file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename));
        $manipulator->addEntityField(new ClassProperty(propertyName: $propertyName, type: 'string', length: 255, nullable: $nullable ?: null, promoted: true));

        $this->assertSame(
            file_get_contents(__DIR__.'/fixtures/add_promoted_property/'.$expectedSourceFilename),
            $manipulator->getSourceCode()
        );
    }

    public static function getAddPromotedEntityFieldToConstructorTests(): \Generator
    {
        yield 'constructor_without_parameters' => [
            'User_with_constructor.php',
            'lastName',
            'User_with_constructor.php',
        ];

        // the signature is split, and the parameter comes before the optional one
        yield 'constructor_on_one_line' => [
            'User_constructor_one_line.php',
            'lastName',
            'User_constructor_one_line.php',
        ];

        // the constructor cannot have two parameters of the same name
        yield 'constructor_with_parameter_of_the_same_name' => [
            'User_constructor_one_line.php',
            'firstName',
            'User_constructor_one_line_same_name.php',
        ];

        yield 'promoted_constructor' => [
            'User_promoted_props.php',
            'lastName',
            'User_promoted_props.php',
        ];

        yield 'promoted_constructor_without_body' => [
            'User_promoted_props_empty_constructor.php',
            'lastName',
            'User_promoted_props_empty_constructor.php',
        ];

        // the parameters stay as close to each other as they were, and the comment is kept
        yield 'promoted_constructor_compact' => [
            'User_promoted_props_compact.php',
            'lastName',
            'User_promoted_props_compact.php',
        ];

        // an optional parameter comes after the other ones
        yield 'promoted_constructor_optional_parameter' => [
            'User_promoted_props.php',
            'lastName',
            'User_promoted_props_optional.php',
            true,
        ];

        // but before the variadic one
        yield 'constructor_with_variadic_parameter' => [
            'User_constructor_variadic.php',
            'lastName',
            'User_constructor_variadic.php',
            true,
        ];
    }

    public function testAddObjectsThatAreAlreadyPromoted()
    {
        $manipulator = new ClassSourceManipulator(file_get_contents(__DIR__.'/fixtures/source/User_promoted_objects.php'));

        // the constructor is given these objects: it must not create them on top of that
        $manipulator->addEmbeddedEntity('address', \App\Entity\Address::class);
        $manipulator->addManyToManyRelation(new RelationManyToMany(
            propertyName: 'recipes',
            targetClassName: \App\Entity\Recipe::class,
            targetPropertyName: 'foods',
            isOwning: true,
        ));

        $this->assertSame(
            file_get_contents(__DIR__.'/fixtures/add_promoted_property/User_promoted_objects.php'),
            $manipulator->getSourceCode()
        );
    }

    public function testAddSeveralPromotedProperties()
    {
        $manipulator = new ClassSourceManipulator(file_get_contents(__DIR__.'/fixtures/source/User_simple.php'));

        $manipulator->addManyToOneRelation(new RelationManyToOne(
            propertyName: 'category',
            targetClassName: \App\Entity\Category::class,
            targetPropertyName: 'foods',
            isOwning: true,
            isOwningSidePromoted: true,
        ));
        $manipulator->addManyToOneRelation(new RelationManyToOne(
            propertyName: 'subCategory',
            targetClassName: \App\Entity\Category::class,
            mapInverseRelation: false,
            isOwning: true,
            isOwningSidePromoted: true,
        ));
        $manipulator->addEntityField(new ClassProperty(propertyName: 'biography', type: 'text', nullable: true, promoted: true));
        $manipulator->addManyToManyRelation(new RelationManyToMany(
            propertyName: 'recipes',
            targetClassName: \App\Entity\Recipe::class,
            targetPropertyName: 'foods',
            isOwning: true,
        ));
        $manipulator->addEntityField(new ClassProperty(propertyName: 'createdAt', type: 'datetime_immutable', promoted: true));

        $this->assertSame(
            file_get_contents(__DIR__.'/fixtures/add_promoted_property/User_simple_several.php'),
            $manipulator->getSourceCode()
        );
    }

    public function testAddEntityFieldWithEnumDefaultValue()
    {
        require_once __DIR__.'/fixtures/enum_helper/src/Enum/Status.php';

        $this->runAddEntityFieldTests(
            file_get_contents(__DIR__.'/fixtures/source/User_simple.php'),
            new ClassProperty(propertyName: 'status', type: 'string', length: 255, options: ['default' => \App\Enum\Status::PUBLISHED], enumType: \App\Enum\Status::class),
            file_get_contents(__DIR__.'/fixtures/add_entity_field/User_simple_enum_default.php')
        );
    }

    public function testAddEntityFieldWithMultipleEnumValues()
    {
        require_once __DIR__.'/fixtures/enum_helper/src/Enum/Status.php';

        // Doctrine infers the "json" type from the array, but not the enum it holds
        $this->runAddEntityFieldTests(
            file_get_contents(__DIR__.'/fixtures/source/User_simple.php'),
            new ClassProperty(propertyName: 'statuses', type: 'json', nullable: true, enumType: \App\Enum\Status::class),
            file_get_contents(__DIR__.'/fixtures/add_entity_field/User_simple_enum_multiple.php')
        );
    }

    /**
     * @dataProvider getAddManyToOneRelationTests
     */
    #[DataProvider('getAddManyToOneRelationTests')]
    public function testAddManyToOneRelation(string $sourceFilename, $expectedSourceFilename, RelationManyToOne $manyToOne)
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

    public static function getAddManyToOneRelationTests(): \Generator
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

        yield 'many_to_one_promoted' => [
            'User_simple.php',
            'User_simple_promoted.php',
            new RelationManyToOne(
                propertyName: 'category',
                targetClassName: \App\Entity\Category::class,
                targetPropertyName: 'foods',
                isOwning: true,
                isOwningSidePromoted: true,
            ),
        ];

        // a nullable relation defaults to null: its parameter is optional
        yield 'many_to_one_promoted_nullable' => [
            'User_simple.php',
            'User_simple_promoted_nullable.php',
            new RelationManyToOne(
                propertyName: 'category',
                targetClassName: \App\Entity\Category::class,
                targetPropertyName: 'foods',
                isOwning: true,
                isNullable: true,
                isOwningSidePromoted: true,
            ),
        ];
    }

    /**
     * @dataProvider getAddOneToManyRelationTests
     */
    #[DataProvider('getAddOneToManyRelationTests')]
    public function testAddOneToManyRelation(string $sourceFilename, string $expectedSourceFilename, RelationOneToMany $oneToMany)
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

    public static function getAddOneToManyRelationTests(): \Generator
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

        yield 'one_to_many_no_duplicate_grouped_use' => [
            'User_with_grouped_use_statements.php',
            'User_with_grouped_use_statements.php',
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

        // the owning side cannot be set to null
        yield 'one_to_many_owning_side_promoted' => [
            'User_simple.php',
            'User_simple_owning_side_promoted.php',
            new RelationOneToMany(
                propertyName: 'avatarPhotos',
                targetClassName: \App\Entity\UserAvatarPhoto::class,
                targetPropertyName: 'user',
                orphanRemoval: true,
                isOwningSidePromoted: true,
            ),
        ];

        // unless it is nullable
        yield 'one_to_many_owning_side_promoted_nullable' => [
            'User_simple.php',
            'User_simple.php',
            new RelationOneToMany(
                propertyName: 'avatarPhotos',
                targetClassName: \App\Entity\UserAvatarPhoto::class,
                targetPropertyName: 'user',
                isNullable: true,
                isOwningSidePromoted: true,
            ),
        ];

        // todo test existing constructor
    }

    /**
     * @dataProvider getAddManyToManyRelationTests
     */
    #[DataProvider('getAddManyToManyRelationTests')]
    public function testAddManyToManyRelation(string $sourceFilename, $expectedSourceFilename, RelationManyToMany $manyToMany)
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

    public static function getAddManyToManyRelationTests(): \Generator
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

        // the collection is created by a constructor that exists, which is otherwise left alone
        yield 'many_to_many_promoted_constructor' => [
            'User_promoted_props.php',
            'User_promoted_props.php',
            new RelationManyToMany(
                propertyName: 'recipes',
                targetClassName: \App\Entity\Recipe::class,
                targetPropertyName: 'foods',
                isOwning: true,
            ),
        ];

        yield 'many_to_many_promoted_constructor_without_body' => [
            'User_promoted_props_empty_constructor.php',
            'User_promoted_props_empty_constructor.php',
            new RelationManyToMany(
                propertyName: 'recipes',
                targetClassName: \App\Entity\Recipe::class,
                targetPropertyName: 'foods',
                isOwning: true,
            ),
        ];
    }

    /**
     * @dataProvider getAddOneToOneRelationTests
     */
    #[DataProvider('getAddOneToOneRelationTests')]
    public function testAddOneToOneRelation(string $sourceFilename, $expectedSourceFilename, RelationOneToOne $oneToOne)
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

    public static function getAddOneToOneRelationTests(): \Generator
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

        yield 'one_to_one_promoted' => [
            'User_simple.php',
            'User_simple_promoted.php',
            new RelationOneToOne(
                propertyName: 'userProfile',
                targetClassName: \App\Entity\UserProfile::class,
                targetPropertyName: 'user',
                isOwning: true,
                isOwningSidePromoted: true,
            ),
        ];

        // a nullable relation defaults to null: its parameter is optional
        yield 'one_to_one_promoted_nullable' => [
            'User_simple.php',
            'User_simple_promoted_nullable.php',
            new RelationOneToOne(
                propertyName: 'userProfile',
                targetClassName: \App\Entity\UserProfile::class,
                targetPropertyName: 'user',
                isOwning: true,
                isNullable: true,
                isOwningSidePromoted: true,
            ),
        ];

        // only the owning side is promoted: nothing changes for the inverse one
        yield 'one_to_one_inverse_owning_side_promoted' => [
            'UserProfile_simple.php',
            'UserProfile_simple_inverse_not_nullable.php',
            new RelationOneToOne(
                propertyName: 'user',
                targetClassName: \App\Entity\User::class,
                targetPropertyName: 'userProfile',
                isOwningSidePromoted: true,
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

    public function testAddInterface()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_simple.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/implements_interface/User_simple.php');

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addInterface(UserInterface::class);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddInterfaceToClassWithOtherInterface()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_simple_with_interface.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/implements_interface/User_simple_with_interface.php');

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addInterface(UserInterface::class);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddMethodBuilder()
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

    public function testAddMethodWithBody()
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

    public function testAddTraitInEmptyClass()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_empty.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_trait/User_with_only_trait.php');

        $manipulator = new ClassSourceManipulator($source);

        $manipulator->addTrait('App\TestTrait');

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddTraitWithProperty()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_simple.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_trait/User_with_prop_trait.php');

        $manipulator = new ClassSourceManipulator($source);

        $manipulator->addTrait('App\TestTrait');

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddTraitWithConstant()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_with_const.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_trait/User_with_const_trait.php');

        $manipulator = new ClassSourceManipulator($source);

        $manipulator->addTrait('App\TestTrait');

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddTraitWithTrait()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_with_trait.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_trait/User_with_trait_trait.php');

        $manipulator = new ClassSourceManipulator($source);

        $manipulator->addTrait('App\TestTrait');

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddTraitAlReadyExists()
    {
        $source = file_get_contents(__DIR__.'/fixtures/add_trait/User_with_trait_trait.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_trait/User_with_trait_trait.php');

        $manipulator = new ClassSourceManipulator($source);

        $manipulator->addTrait('App\TraitAlreadyHere');

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddConstructor()
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

    public function testAddConstructorInClassContainsPropsAndMethods()
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

    public function testAddConstructorInClassContainsOnlyConstants()
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

    public function testAddConstructorInClassContainsConstructor()
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

    /**
     * @requires PHP >= 8.4
     */
    #[\PHPUnit\Framework\Attributes\RequiresPhp('>= 8.4')]
    public function testParsingPhp84PropertyHooks()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_property_hooks.php');

        // This should not throw a PhpParser\Error
        $manipulator = new ClassSourceManipulator($source);

        // Verify we can still work with the class
        $this->assertStringContainsString('class User', $manipulator->getSourceCode());
    }

    /**
     * @requires PHP >= 8.4
     */
    #[\PHPUnit\Framework\Attributes\RequiresPhp('>= 8.4')]
    public function testAddPropertyToClassWithPropertyHooks()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/User_property_hooks.php');

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addProperty(name: 'newProp', propertyType: '?string');

        $this->assertStringContainsString('private ?string $newProp', $manipulator->getSourceCode());
    }
}
