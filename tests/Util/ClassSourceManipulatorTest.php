<?php

namespace Symfony\Bundle\MakerBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Doctrine\RelationManyToMany;
use Symfony\Bundle\MakerBundle\Doctrine\RelationManyToOne;
use Symfony\Bundle\MakerBundle\Doctrine\RelationOneToMany;
use Symfony\Bundle\MakerBundle\Doctrine\RelationOneToOne;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;

class ClassSourceManipulatorTest extends TestCase
{
    /**
     * @dataProvider getAddPropertyTests
     */
    public function testAddProperty(string $sourceFilename, $propertyName, array $commentLines, $expectedSourceFilename)
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_property/'.$expectedSourceFilename);

        $manipulator = new ClassSourceManipulator($source);
        $method = (new \ReflectionObject($manipulator))->getMethod('addProperty');
        $method->setAccessible(true);
        $method->invoke($manipulator, $propertyName, $commentLines);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function getAddPropertyTests()
    {
        yield 'normal_property_add' => [
            'User_simple.php',
            'fooProp',
            [],
            'User_simple.php'
        ];

        yield 'with_no_properties_and_comment' => [
            'User_no_props.php',
            'fooProp',
            [
                '@var string',
                '@internal'
            ],
            'User_no_props.php'
        ];

        yield 'no_properties_and_constants' => [
            'User_no_props_constants.php',
            'fooProp',
            [],
            'User_no_props_constants.php'
        ];

        yield 'property_empty_class' => [
            'User_empty.php',
            'fooProp',
            [],
            'User_empty.php'
        ];
    }

    /**
     * @dataProvider getAddGetterTests
     */
    public function testAddGetter(string $sourceFilename, string $propertyName, string $type, array $commentLines, $expectedSourceFilename)
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_getter/'.$expectedSourceFilename);

        $manipulator = new ClassSourceManipulator($source);
        $method = (new \ReflectionObject($manipulator))->getMethod('addGetter');
        $method->setAccessible(true);
        $method->invoke($manipulator, $propertyName, $type, true, $commentLines);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function getAddGetterTests()
    {
        yield 'normal_getter_add' => [
            'User_simple.php',
            'fooProp',
            'string',
            [],
            'User_simple.php'
        ];

        yield 'getter_no_props_comments' => [
            'User_no_props.php',
            'fooProp',
            'string',
            [
                '@return string',
                '@internal'
            ],
            'User_no_props.php'
        ];

        yield 'getter_empty_class' => [
            'User_empty.php',
            'fooProp',
            'string',
            [],
            'User_empty.php'
        ];
    }

    /**
     * @dataProvider getAddSetterTests
     */
    public function testAddSetter(string $sourceFilename, string $propertyName, string $type, bool $isNullable, array $commentLines, $expectedSourceFilename)
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_setter/'.$expectedSourceFilename);

        $manipulator = new ClassSourceManipulator($source);
        $method = (new \ReflectionObject($manipulator))->getMethod('addSetter');
        $method->setAccessible(true);
        $method->invoke($manipulator, $propertyName, $type, $isNullable, $commentLines);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function getAddSetterTests()
    {
        yield 'normal_setter_add' => [
            'User_simple.php',
            'fooProp',
            'string',
            false,
            [],
            'User_simple.php'
        ];

        yield 'setter_no_props_comments' => [
            'User_no_props.php',
            'fooProp',
            'string',
            true,
            [
                '@param string $fooProp',
                '@internal'
            ],
            'User_no_props.php'
        ];

        yield 'setter_empty_class' => [
            'User_empty.php',
            'fooProp',
            'string',
            false,
            [],
            'User_empty.php'
        ];
    }

    /**
     * @dataProvider getAnnotationTests
     */
    public function testBuildAnnotationLine(string $annotationClass, array $annotationOptions, string $expectedAnnotation)
    {
        $manipulator = new ClassSourceManipulator('');
        $method = (new \ReflectionObject($manipulator))->getMethod('buildAnnotationLine');
        $method->setAccessible(true);
        $actualAnnotation = $method->invoke($manipulator, $annotationClass, $annotationOptions);

        $this->assertSame($expectedAnnotation, $actualAnnotation);
    }

    public function getAnnotationTests()
    {
        yield 'empty_annotation' => [
            '@ORM\Column',
            [],
            '@ORM\Column()'
        ];

        yield 'complex_annotation' => [
            '@ORM\Column',
            [
                'name' => 'firstName',
                'length' => 10,
                'nullable' => false,
            ],
            '@ORM\Column(name="firstName", length=10, nullable=false)'
        ];
    }

    /**
     * @dataProvider getAddEntityFieldTests
     */
    public function testAddEntityField(string $sourceFilename, string $propertyName, array $fieldOptions, bool $onlyMethods, $expectedSourceFilename)
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_entity_field/'.$expectedSourceFilename);

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addEntityField($propertyName, $fieldOptions, $onlyMethods);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function getAddEntityFieldTests()
    {
        yield 'entity_normal_add' => [
            'User_simple.php',
            'fooProp',
            [
                'type' => 'string',
                'length' => 255,
                'nullable' => false,
                'options' => ['comment' => 'new field']
            ],
            false, // only methods
            'User_simple.php'
        ];

        yield 'entity_add_datetime' => [
            'User_simple.php',
            'createdAt',
            [
                'type' => 'datetime',
                'nullable' => true,
            ],
            false, // only methods
            'User_simple_datetime.php'
        ];

        yield 'entity_field_only_methods' => [
            'User_some_props.php',
            'firstName',
            [
                'type' => 'string',
                'length' => 255,
                'nullable' => false,
            ],
            true, // only methods
            'User_some_props_only_methods.php'
        ];
    }

    /**
     * @dataProvider getAddManyToOneRelationTests
     */
    public function testAddManyToOneRelation(string $sourceFilename, $expectedSourceFilename, RelationManyToOne $manyToOne)
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_many_to_one_relation/'.$expectedSourceFilename);

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addManyToOneRelation($manyToOne);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function getAddManyToOneRelationTests()
    {
        yield 'many_to_one_not_nullable' => [
            'User_simple.php',
            'User_simple_not_nullable.php',
            (new RelationManyToOne())
                ->setPropertyName('category')
                ->setTargetClassName('App\Entity\Category')
                ->setTargetPropertyName('foods')
                ->setIsNullable(false)
        ];

        yield 'many_to_one_nullable' => [
            'User_simple.php',
            'User_simple_nullable.php',
            (new RelationManyToOne())
                ->setPropertyName('category')
                ->setTargetClassName('App\Entity\Category')
                ->setTargetPropertyName('foods')
                ->setIsNullable(true)
        ];

        yield 'many_to_one_other_namespace' => [
            'User_simple.php',
            'User_simple_other_namespace.php',
            (new RelationManyToOne())
                ->setPropertyName('category')
                ->setTargetClassName('Foo\Entity\Category')
                ->setTargetPropertyName('foods')
                ->setIsNullable(true)
        ];

        yield 'many_to_one_empty_other_namespace' => [
            'User_empty.php',
            'User_empty_other_namespace.php',
            (new RelationManyToOne())
                ->setPropertyName('category')
                ->setTargetClassName('Foo\Entity\Category')
                ->setTargetPropertyName('foods')
                ->setIsNullable(true)
        ];

        yield 'many_to_one_no_inverse' => [
            'User_simple.php',
            'User_simple_no_inverse.php',
            (new RelationManyToOne())
                ->setPropertyName('category')
                ->setTargetClassName('App\Entity\Category')
                ->setTargetPropertyName('foods')
                ->setIsNullable(true)
                ->setMapInverseRelation(false)
        ];
    }

    /**
     * @dataProvider getAddOneToManyRelationTests
     */
    public function testAddOneToManyRelation(string $sourceFilename, $expectedSourceFilename, RelationOneToMany $oneToMany)
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_one_to_many_relation/'.$expectedSourceFilename);

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addOneToManyRelation($oneToMany);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function getAddOneToManyRelationTests()
    {
        yield 'one_to_many_simple' => [
            'User_simple.php',
            'User_simple.php',
            (new RelationOneToMany())
                ->setPropertyName('avatarPhotos')
                ->setTargetClassName('App\Entity\UserAvatarPhoto')
                ->setTargetPropertyName('user')
                ->setOrphanRemoval(false)
        ];

        // interesting also because the source file has its
        // use statements out of alphabetical order
        yield 'one_to_many_simple_no_duplicate_use' => [
            'User_with_use_statements.php',
            'User_with_use_statements.php',
            (new RelationOneToMany())
                ->setPropertyName('avatarPhotos')
                ->setTargetClassName('App\Entity\UserAvatarPhoto')
                ->setTargetPropertyName('user')
                ->setOrphanRemoval(false)
        ];

        yield 'one_to_many_orphan_removal' => [
            'User_simple.php',
            'User_simple_orphan_removal.php',
            (new RelationOneToMany())
                ->setPropertyName('avatarPhotos')
                ->setTargetClassName('App\Entity\UserAvatarPhoto')
                ->setTargetPropertyName('user')
                ->setOrphanRemoval(true)
        ];

        // todo test existing constructor
    }

    /**
     * @dataProvider getAddManyToManyRelationTests
     */
    public function testAddManyToManyRelation(string $sourceFilename, $expectedSourceFilename, RelationManyToMany $manyToMany)
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_many_to_many_relation/'.$expectedSourceFilename);

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addManyToManyRelation($manyToMany);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function getAddManyToManyRelationTests()
    {
        yield 'many_to_many_owning' => [
            'User_simple.php',
            'User_simple_owning.php',
            (new RelationManyToMany())
                ->setPropertyName('recipes')
                ->setTargetClassName('App\Entity\Recipe')
                ->setTargetPropertyName('foods')
                ->setIsOwning(true)
        ];

        yield 'many_to_many_inverse' => [
            'User_simple.php',
            'User_simple_inverse.php',
            (new RelationManyToMany())
                ->setPropertyName('recipes')
                ->setTargetClassName('App\Entity\Recipe')
                ->setTargetPropertyName('foods')
                ->setIsOwning(false)
        ];

        yield 'many_to_many_owning' => [
            'User_simple.php',
            'User_simple_no_inverse.php',
            (new RelationManyToMany())
                ->setPropertyName('recipes')
                ->setTargetClassName('App\Entity\Recipe')
                ->setTargetPropertyName('foods')
                ->setIsOwning(true)
                ->setMapInverseRelation(false)
        ];
    }

    /**
     * @dataProvider getAddOneToOneRelationTests
     */
    public function testAddOneToOneRelation(string $sourceFilename, $expectedSourceFilename, RelationOneToOne $oneToOne)
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/'.$sourceFilename);
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_one_to_one_relation/'.$expectedSourceFilename);

        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addOneToOneRelation($oneToOne);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function getAddOneToOneRelationTests()
    {
        yield 'one_to_one_owning' => [
            'User_simple.php',
            'User_simple_owning.php',
            (new RelationOneToOne())
                ->setPropertyName('userProfile')
                ->setTargetClassName('App\Entity\UserProfile')
                ->setTargetPropertyName('user')
                ->setIsNullable(true)
                ->setIsOwning(true)
        ];

        // a relationship to yourself - return type is self
        yield 'one_to_one_owning_self' => [
            'User_simple.php',
            'User_simple_self.php',
            (new RelationOneToOne())
                ->setPropertyName('embeddedUser')
                ->setTargetClassName('App\Entity\User')
                ->setTargetPropertyName('user')
                ->setIsNullable(true)
                ->setIsOwning(true)
        ];

        yield 'one_to_one_inverse' => [
            'UserProfile_simple.php',
            'UserProfile_simple_inverse.php',
            (new RelationOneToOne())
                ->setPropertyName('user')
                ->setTargetClassName('App\Entity\User')
                ->setTargetPropertyName('userProfile')
                ->setIsNullable(true)
                ->setIsOwning(false)
        ];

        yield 'one_to_one_inverse_not_nullable' => [
            'UserProfile_simple.php',
            'UserProfile_simple_inverse_not_nullable.php',
            (new RelationOneToOne())
                ->setPropertyName('user')
                ->setTargetClassName('App\Entity\User')
                ->setTargetPropertyName('userProfile')
                ->setIsNullable(false)
                ->setIsOwning(false)
        ];

        yield 'one_to_one_no_inverse' => [
            'User_simple.php',
            'User_simple_no_inverse.php',
            (new RelationOneToOne())
                ->setPropertyName('userProfile')
                ->setTargetClassName('App\Entity\UserProfile')
                //->setTargetPropertyName('user')
                ->setIsNullable(true)
                ->setIsOwning(true)
                ->setMapInverseRelation(false)
        ];

        yield 'one_to_one_no_inverse_not_nullable' => [
            'User_simple.php',
            'User_simple_no_inverse_not_nullable.php',
            (new RelationOneToOne())
                ->setPropertyName('userProfile')
                ->setTargetClassName('App\Entity\UserProfile')
                //->setTargetPropertyName('user')
                ->setIsNullable(false)
                ->setIsOwning(true)
                ->setMapInverseRelation(false)
        ];

        yield 'avoid_duplicate_use_statement' => [
            'User_with_use_statements.php',
            'User_with_use_statements_avoid_duplicate_use.php',
            (new RelationOneToOne())
                ->setPropertyName('userProfile')
                ->setTargetClassName('App\OtherEntity\UserProfile')
                ->setTargetPropertyName('user')
                ->setIsNullable(true)
                ->setIsOwning(true)
        ];

        yield 'avoid_duplicate_use_statement_with_alias' => [
            'User_with_use_statements.php',
            'User_with_use_statements_avoid_duplicate_use_alias.php',
            (new RelationOneToOne())
                ->setPropertyName('category')
                ->setTargetClassName('App\OtherEntity\Category')
                ->setTargetPropertyName('user')
                ->setIsNullable(true)
                ->setIsOwning(true)
        ];
    }
}
