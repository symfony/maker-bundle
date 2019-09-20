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
use PhpParser\Builder\Param;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Doctrine\RelationManyToMany;
use Symfony\Bundle\MakerBundle\Doctrine\RelationManyToOne;
use Symfony\Bundle\MakerBundle\Doctrine\RelationOneToMany;
use Symfony\Bundle\MakerBundle\Doctrine\RelationOneToOne;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Component\Security\Core\User\UserInterface;

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
            'legacy/User_simple.php',
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
            'legacy/User_empty.php',
            'fooProp',
            [],
            'User_empty.php',
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
            'legacy/User_simple.php',
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
            'User_simple_bool.php'
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
            'legacy/User_empty.php',
            'fooProp',
            'string',
            [],
            'User_empty.php',
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
            'legacy/User_simple.php',
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
            'legacy/User_empty.php',
            'fooProp',
            'string',
            false,
            [],
            'User_empty.php',
        ];
    }

    /**
     * @dataProvider getAttributeClassTests
     */
    public function testAddAttributeToClass(string $sourceFilename, string $expectedSourceFilename, string $attributeClass, array $attributeOptions, string $attributePrefix = null): void
    {
        // @legacy Remove conditional when PHP < 8.0 support is dropped.
        if ((\PHP_VERSION_ID < 80000)) {
            $this->markTestSkipped('Requires PHP >= PHP 8.0');
        }

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
            '@ORM\Column()',
        ];

        yield 'complex_annotation' => [
            '@ORM\Column',
            [
                'name' => 'firstName',
                'length' => 10,
                'nullable' => false,
            ],
            '@ORM\Column(name="firstName", length=10, nullable=false)',
        ];
    }

    /**
     * @dataProvider getAddEntityFieldTests
     */
    public function testAddEntityField(string $sourceFilename, string $propertyName, array $fieldOptions, $expectedSourceFilename): void
    {
        $sourcePath = __DIR__.'/fixtures/source';
        $expectedPath = __DIR__.'/fixtures/add_entity_field';

        // Legacy Annotation Tests
        $this->runAddEntityFieldTests(
            file_get_contents(sprintf('%s/legacy/%s', $sourcePath, $sourceFilename)),
            $propertyName,
            $fieldOptions,
            file_get_contents(sprintf('%s/legacy/%s', $expectedPath, $expectedSourceFilename)),
            false
        );

        // Run Attribute Tests
        if ((\PHP_VERSION_ID >= 80000)) {
            $this->runAddEntityFieldTests(
                file_get_contents(sprintf('%s/%s', $sourcePath, $sourceFilename)),
                $propertyName,
                $fieldOptions,
                file_get_contents(sprintf('%s/%s', $expectedPath, $expectedSourceFilename)),
                true
            );
        }
    }

    private function runAddEntityFieldTests(string $source, string $propertyName, array $fieldOptions, string $expected, bool $php8): void
    {
        $manipulator = new ClassSourceManipulator($source, false, !$php8, true, $php8);
        $manipulator->addEntityField($propertyName, $fieldOptions);

        $this->assertSame($expected, $manipulator->getSourceCode());
    }

    public function getAddEntityFieldTests(): \Generator
    {
        yield 'entity_normal_add' => [
            'User_simple.php',
            'fooProp',
            [
                'type' => 'string',
                'length' => 255,
                'nullable' => false,
                'options' => ['comment' => 'new field'],
            ],
            'User_simple.php',
        ];

        yield 'entity_add_datetime' => [
            'User_simple.php',
            'createdAt',
            [
                'type' => 'datetime',
                'nullable' => true,
            ],
            'User_simple_datetime.php',
        ];

        yield 'entity_field_property_already_exists' => [
            'User_some_props.php',
            'firstName',
            [
                'type' => 'string',
                'length' => 255,
                'nullable' => false,
            ],
            'User_simple_prop_already_exists.php',
        ];

        yield 'entity_field_property_zero' => [
            'User_simple.php',
            'decimal',
            [
                'type' => 'decimal',
                'precision' => 6,
                'scale' => 0,
            ],
            'User_simple_prop_zero.php',
        ];

        yield 'entity_add_object' => [
            'User_simple.php',
            'someObject',
            [
                'type' => 'object',
            ],
            'User_simple_object.php',
        ];
    }

    /**
     * @dataProvider getAddManyToOneRelationTests
     */
    public function testAddManyToOneRelation(string $sourceFilename, $expectedSourceFilename, RelationManyToOne $manyToOne): void
    {
        $sourcePath = __DIR__.'/fixtures/source';
        $expectedPath = __DIR__.'/fixtures/add_many_to_one_relation';

        // Legacy Annotation Tests
        $this->runAddManyToOneRelationTests(
            file_get_contents(sprintf('%s/legacy/%s', $sourcePath, $sourceFilename)),
            file_get_contents(sprintf('%s/legacy/%s', $expectedPath, $expectedSourceFilename)),
            $manyToOne,
            false
        );

        // Run Attribute Tests
        if ((\PHP_VERSION_ID >= 80000)) {
            $this->runAddManyToOneRelationTests(
                file_get_contents(sprintf('%s/%s', $sourcePath, $sourceFilename)),
                file_get_contents(sprintf('%s/%s', $expectedPath, $expectedSourceFilename)),
                $manyToOne,
                true
            );
        }
    }

    public function runAddManyToOneRelationTests(string $source, string $expected, RelationManyToOne $manyToOne, bool $php8): void
    {
        $manipulator = new ClassSourceManipulator($source, false, !$php8, true, $php8);
        $manipulator->addManyToOneRelation($manyToOne);

        $this->assertSame($expected, $manipulator->getSourceCode());
    }

    public function getAddManyToOneRelationTests(): \Generator
    {
        yield 'many_to_one_not_nullable' => [
            'User_simple.php',
            'User_simple_not_nullable.php',
            (new RelationManyToOne())
                ->setPropertyName('category')
                ->setTargetClassName('App\Entity\Category')
                ->setTargetPropertyName('foods')
                ->setIsNullable(false),
        ];

        yield 'many_to_one_nullable' => [
            'User_simple.php',
            'User_simple_nullable.php',
            (new RelationManyToOne())
                ->setPropertyName('category')
                ->setTargetClassName('App\Entity\Category')
                ->setTargetPropertyName('foods')
                ->setIsNullable(true),
        ];

        yield 'many_to_one_other_namespace' => [
            'User_simple.php',
            'User_simple_other_namespace.php',
            (new RelationManyToOne())
                ->setPropertyName('category')
                ->setTargetClassName('Foo\Entity\Category')
                ->setTargetPropertyName('foods')
                ->setIsNullable(true),
        ];

        yield 'many_to_one_empty_other_namespace' => [
            'User_empty.php',
            'User_empty_other_namespace.php',
            (new RelationManyToOne())
                ->setPropertyName('category')
                ->setTargetClassName('Foo\Entity\Category')
                ->setTargetPropertyName('foods')
                ->setIsNullable(true),
        ];

        yield 'many_to_one_same_and_other_namespaces' => [
            'User_with_relation.php',
            'User_with_relation_same_and_other_namespaces.php',
            (new RelationManyToOne())
                ->setPropertyName('subCategory')
                ->setTargetClassName('App\Entity\SubDirectory\Category')
                ->setTargetPropertyName('foods')
                ->setIsNullable(true),
        ];

        yield 'many_to_one_no_inverse' => [
            'User_simple.php',
            'User_simple_no_inverse.php',
            (new RelationManyToOne())
                ->setPropertyName('category')
                ->setTargetClassName('App\Entity\Category')
                ->setTargetPropertyName('foods')
                ->setIsNullable(true)
                ->setMapInverseRelation(false),
        ];
    }

    /**
     * @dataProvider getAddOneToManyRelationTests
     */
    public function testAddOneToManyRelation(string $sourceFilename, string $expectedSourceFilename, RelationOneToMany $oneToMany)
    {
        $sourcePath = __DIR__.'/fixtures/source';
        $expectedPath = __DIR__.'/fixtures/add_one_to_many_relation';

        // Legacy Annotation Tests
        $this->runAddOneToManyRelationTests(
            file_get_contents(sprintf('%s/legacy/%s', $sourcePath, $sourceFilename)),
            file_get_contents(sprintf('%s/legacy/%s', $expectedPath, $expectedSourceFilename)),
            $oneToMany,
            false
        );

        // Run Attribute Tests
        if ((\PHP_VERSION_ID >= 80000)) {
            $this->runAddOneToManyRelationTests(
                file_get_contents(sprintf('%s/%s', $sourcePath, $sourceFilename)),
                file_get_contents(sprintf('%s/%s', $expectedPath, $expectedSourceFilename)),
                $oneToMany,
                true
            );
        }
    }

    private function runAddOneToManyRelationTests(string $source, string $expected, RelationOneToMany $oneToMany, bool $php8): void
    {
        $manipulator = new ClassSourceManipulator($source, false, !$php8, true, $php8);
        $manipulator->addOneToManyRelation($oneToMany);

        $this->assertSame($expected, $manipulator->getSourceCode());
    }

    public function getAddOneToManyRelationTests(): \Generator
    {
        yield 'one_to_many_simple' => [
            'User_simple.php',
            'User_simple.php',
            (new RelationOneToMany())
                ->setPropertyName('avatarPhotos')
                ->setTargetClassName('App\Entity\UserAvatarPhoto')
                ->setTargetPropertyName('user')
                ->setOrphanRemoval(false),
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
                ->setOrphanRemoval(false),
        ];

        yield 'one_to_many_orphan_removal' => [
            'User_simple.php',
            'User_simple_orphan_removal.php',
            (new RelationOneToMany())
                ->setPropertyName('avatarPhotos')
                ->setTargetClassName('App\Entity\UserAvatarPhoto')
                ->setTargetPropertyName('user')
                ->setOrphanRemoval(true),
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

        // Legacy Annotation Tests
        $this->runAddManyToManyRelationTest(
            file_get_contents(sprintf('%s/legacy/%s', $sourcePath, $sourceFilename)),
            file_get_contents(sprintf('%s/legacy/%s', $expectedPath, $expectedSourceFilename)),
            $manyToMany,
            false
        );

        // Run Attribute Tests
        if ((\PHP_VERSION_ID >= 80000)) {
            $this->runAddManyToManyRelationTest(
                file_get_contents(sprintf('%s/%s', $sourcePath, $sourceFilename)),
                file_get_contents(sprintf('%s/%s', $expectedPath, $expectedSourceFilename)),
                $manyToMany,
                true
            );
        }
    }

    private function runAddManyToManyRelationTest(string $source, string $expected, RelationManyToMany $manyToMany, bool $php8): void
    {
        $manipulator = new ClassSourceManipulator($source, false, !$php8, true, $php8);
        $manipulator->addManyToManyRelation($manyToMany);

        $this->assertSame($expected, $manipulator->getSourceCode());
    }

    public function getAddManyToManyRelationTests(): \Generator
    {
        yield 'many_to_many_owning' => [
            'User_simple.php',
            'User_simple_owning.php',
            (new RelationManyToMany())
                ->setPropertyName('recipes')
                ->setTargetClassName('App\Entity\Recipe')
                ->setTargetPropertyName('foods')
                ->setIsOwning(true),
        ];

        yield 'many_to_many_inverse' => [
            'User_simple.php',
            'User_simple_inverse.php',
            (new RelationManyToMany())
                ->setPropertyName('recipes')
                ->setTargetClassName('App\Entity\Recipe')
                ->setTargetPropertyName('foods')
                ->setIsOwning(false),
        ];

        yield 'many_to_many_owning_inverse' => [
            'User_simple.php',
            'User_simple_no_inverse.php',
            (new RelationManyToMany())
                ->setPropertyName('recipes')
                ->setTargetClassName('App\Entity\Recipe')
                ->setTargetPropertyName('foods')
                ->setIsOwning(true)
                ->setMapInverseRelation(false),
        ];
    }

    /**
     * @dataProvider getAddOneToOneRelationTests
     */
    public function testAddOneToOneRelation(string $sourceFilename, $expectedSourceFilename, RelationOneToOne $oneToOne): void
    {
        $sourcePath = __DIR__.'/fixtures/source';
        $expectedPath = __DIR__.'/fixtures/add_one_to_one_relation';

        // Legacy Annotation Tests
        $this->runAddOneToOneRelation(
            file_get_contents(sprintf('%s/legacy/%s', $sourcePath, $sourceFilename)),
            file_get_contents(sprintf('%s/legacy/%s', $expectedPath, $expectedSourceFilename)),
            $oneToOne,
            false
        );

        // Run Attribute Tests
        if ((\PHP_VERSION_ID >= 80000)) {
            $this->runAddOneToOneRelation(
                file_get_contents(sprintf('%s/%s', $sourcePath, $sourceFilename)),
                file_get_contents(sprintf('%s/%s', $expectedPath, $expectedSourceFilename)),
                $oneToOne,
                true
            );
        }
    }

    private function runAddOneToOneRelation(string $source, string $expected, RelationOneToOne $oneToOne, bool $php8): void
    {
        $manipulator = new ClassSourceManipulator($source, false, !$php8, true, $php8);
        $manipulator->addOneToOneRelation($oneToOne);

        $this->assertSame($expected, $manipulator->getSourceCode());
    }

    public function getAddOneToOneRelationTests(): \Generator
    {
        yield 'one_to_one_owning' => [
            'User_simple.php',
            'User_simple_owning.php',
            (new RelationOneToOne())
                ->setPropertyName('userProfile')
                ->setTargetClassName('App\Entity\UserProfile')
                ->setTargetPropertyName('user')
                ->setIsNullable(true)
                ->setIsOwning(true),
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
                ->setIsOwning(true),
        ];

        yield 'one_to_one_inverse' => [
            'UserProfile_simple.php',
            'UserProfile_simple_inverse.php',
            (new RelationOneToOne())
                ->setPropertyName('user')
                ->setTargetClassName('App\Entity\User')
                ->setTargetPropertyName('userProfile')
                ->setIsNullable(true)
                ->setIsOwning(false),
        ];

        yield 'one_to_one_inverse_not_nullable' => [
            'UserProfile_simple.php',
            'UserProfile_simple_inverse_not_nullable.php',
            (new RelationOneToOne())
                ->setPropertyName('user')
                ->setTargetClassName('App\Entity\User')
                ->setTargetPropertyName('userProfile')
                ->setIsNullable(false)
                ->setIsOwning(false),
        ];

        yield 'one_to_one_no_inverse' => [
            'User_simple.php',
            'User_simple_no_inverse.php',
            (new RelationOneToOne())
                ->setPropertyName('userProfile')
                ->setTargetClassName('App\Entity\UserProfile')
                ->setIsNullable(true)
                ->setIsOwning(true)
                ->setMapInverseRelation(false),
        ];

        yield 'one_to_one_no_inverse_not_nullable' => [
            'User_simple.php',
            'User_simple_no_inverse_not_nullable.php',
            (new RelationOneToOne())
                ->setPropertyName('userProfile')
                ->setTargetClassName('App\Entity\UserProfile')
                ->setIsNullable(false)
                ->setIsOwning(true)
                ->setMapInverseRelation(false),
        ];

        yield 'avoid_duplicate_use_statement' => [
            'User_with_use_statements.php',
            'User_with_use_statements_avoid_duplicate_use.php',
            (new RelationOneToOne())
                ->setPropertyName('userProfile')
                ->setTargetClassName('App\OtherEntity\UserProfile')
                ->setTargetPropertyName('user')
                ->setIsNullable(true)
                ->setIsOwning(true),
        ];

        yield 'avoid_duplicate_use_statement_with_alias' => [
            'User_with_use_statements.php',
            'User_with_use_statements_avoid_duplicate_use_alias.php',
            (new RelationOneToOne())
                ->setPropertyName('category')
                ->setTargetClassName('App\OtherEntity\Category')
                ->setTargetPropertyName('user')
                ->setIsNullable(true)
                ->setIsOwning(true),
        ];
    }

    public function testGenerationWithTabs()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/ProductWithTabs.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/with_tabs/ProductWithTabs.php');

        $manipulator = new ClassSourceManipulator($source);

        $method = (new \ReflectionObject($manipulator))->getMethod('addProperty');
        $method->setAccessible(true);
        $method->invoke($manipulator, 'name', ['@ORM\Column(type="string", length=255)']);

        $method = (new \ReflectionObject($manipulator))->getMethod('addGetter');
        $method->setAccessible(true);
        $method->invoke($manipulator, 'id', 'int', false);

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddInterface()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/legacy/User_simple.php');
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
        $source = file_get_contents(__DIR__.'/fixtures/source/legacy/User_empty.php');
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
            (new Param('param'))->setTypeHint('string')
        );
        $manipulator->addMethodBody($methodBuilder,
<<<'CODE'
<?php
return new JsonResponse(['param' => $param]);
CODE
        );
        $manipulator->addMethodBuilder($methodBuilder);
        $manipulator->addUseStatementIfNecessary('Symfony\\Component\\HttpFoundation\\JsonResponse');
        $manipulator->addUseStatementIfNecessary('Symfony\\Component\\Routing\\Annotation\\Route');

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    /**
     * @dataProvider getTestsForAddAnnotationToClass
     */
    public function testAddAnnotationToClass(string $source, string $expectedSource)
    {
        $manipulator = new ClassSourceManipulator($source);
        $manipulator->addAnnotationToClass('Bar\\SomeAnnotation', [
            'message' => 'Foo',
        ]);

        $this->assertEquals($expectedSource, $manipulator->getSourceCode());
    }

    public function getTestsForAddAnnotationToClass()
    {
        yield 'no_doc_block' => [
<<<EOF
<?php

namespace Acme;

class Foo
{
}
EOF
,
<<<EOF
<?php

namespace Acme;

use Bar\SomeAnnotation;

/**
 * @SomeAnnotation(message="Foo")
 */
class Foo
{
}
EOF
];

        yield 'normal_doc_block' => [
<<<EOF
<?php

namespace Acme;

/**
 * I'm a class!
 */
class Foo
{
}
EOF
,
<<<EOF
<?php

namespace Acme;

use Bar\SomeAnnotation;

/**
 * I'm a class!
 * @SomeAnnotation(message="Foo")
 */
class Foo
{
}
EOF
];

        yield 'simple_inline_doc_block' => [
<<<EOF
<?php

namespace Acme;

/** I'm a class! */
class Foo
{
}
EOF
,
<<<EOF
<?php

namespace Acme;

use Bar\SomeAnnotation;

/**
 * I'm a class!
 * @SomeAnnotation(message="Foo")
 */
class Foo
{
}
EOF
        ];

        yield 'weird_inline_doc_block' => [
<<<EOF
<?php

namespace Acme;

/** **I'm a class!** ***/
class Foo
{
}
EOF
,
<<<EOF
<?php

namespace Acme;

use Bar\SomeAnnotation;

/**
 * **I'm a class!**
 * @SomeAnnotation(message="Foo")
 ***/
class Foo
{
}
EOF
];
    }

    public function testAddTraitInEmptyClass()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/legacy/User_empty.php');
        $expectedSource = file_get_contents(__DIR__.'/fixtures/add_trait/User_with_only_trait.php');

        $manipulator = new ClassSourceManipulator($source);

        $manipulator->addTrait('App\TestTrait');

        $this->assertSame($expectedSource, $manipulator->getSourceCode());
    }

    public function testAddTraitWithProperty()
    {
        $source = file_get_contents(__DIR__.'/fixtures/source/legacy/User_simple.php');
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
        $source = file_get_contents(__DIR__.'/fixtures/source/legacy/User_empty.php');
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
        $source = file_get_contents(__DIR__.'/fixtures/source/legacy/User_simple.php');
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
}
