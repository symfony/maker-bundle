<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use PhpParser\Builder;
use PhpParser\BuilderHelpers;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Doctrine\BaseCollectionRelation;
use Symfony\Bundle\MakerBundle\Doctrine\BaseRelation;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Doctrine\RelationManyToMany;
use Symfony\Bundle\MakerBundle\Doctrine\RelationManyToOne;
use Symfony\Bundle\MakerBundle\Doctrine\RelationOneToMany;
use Symfony\Bundle\MakerBundle\Doctrine\RelationOneToOne;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassProperty;

/**
 * @internal
 */
final class ClassSourceManipulator
{
    private const CONTEXT_OUTSIDE_CLASS = 'outside_class';
    private const CONTEXT_CLASS = 'class';
    private const CONTEXT_CLASS_METHOD = 'class_method';
    private const DEFAULT_VALUE_NONE = '__default_value_none';

    private Parser $parser;
    private PrettyPrinter $printer;
    private ?ConsoleStyle $io = null;

    private ?array $oldStmts = null;
    private array $oldTokens = [];
    private array $newStmts = [];

    private array $pendingComments = [];

    public function __construct(
        private string $sourceCode,
        private bool $overwrite = false,
        private bool $useAttributesForDoctrineMapping = true,
    ) {
        $this->parser = (new ParserFactory())->createForHostVersion();
        $this->printer = new PrettyPrinter();

        $this->setSourceCode($sourceCode);
    }

    public function setIo(ConsoleStyle $io): void
    {
        $this->io = $io;
    }

    public function getSourceCode(): string
    {
        return $this->sourceCode;
    }

    public function addEntityField(ClassProperty $mapping): void
    {
        $typeHint = DoctrineHelper::getPropertyTypeForColumn($mapping->type);
        if ($typeHint && DoctrineHelper::canColumnTypeBeInferredByPropertyType($mapping->type, $typeHint)) {
            $mapping->needsTypeHint = false;
        }

        if ($mapping->needsTypeHint) {
            $typeConstant = DoctrineHelper::getTypeConstant($mapping->type);
            if ($typeConstant) {
                $this->addUseStatementIfNecessary(Types::class);
                $mapping->type = $typeConstant;
            }
        }

        // 2) USE property type on property below, nullable
        // 3) If default value, then NOT nullable

        $nullable = $mapping->nullable ?? false;

        $attributes[] = $this->buildAttributeNode(Column::class, $mapping->getAttributes(), 'ORM');

        $defaultValue = null;
        $commentLines = [];

        if (null !== $mapping->enumType) {
            if ('array' === $typeHint) {
                // still need to add the use statement
                $this->addUseStatementIfNecessary($mapping->enumType);

                $commentLines = [\sprintf('@return %s[]', Str::getShortClassName($mapping->enumType))];
                if ($nullable) {
                    $commentLines[0] = \sprintf('%s|null', $commentLines[0]);
                } else {
                    $defaultValue = new Node\Expr\Array_([], ['kind' => Node\Expr\Array_::KIND_SHORT]);
                }
            } else {
                $typeHint = $this->addUseStatementIfNecessary($mapping->enumType);
            }
        } elseif ('array' === $typeHint && !$nullable) {
            $defaultValue = new Node\Expr\Array_([], ['kind' => Node\Expr\Array_::KIND_SHORT]);
        } elseif ($typeHint && '\\' === $typeHint[0] && false !== strpos($typeHint, '\\', 1)) {
            $typeHint = $this->addUseStatementIfNecessary(substr($typeHint, 1));
        }

        $propertyType = $typeHint;
        if ($propertyType && !$defaultValue && 'mixed' !== $propertyType) {
            // all property types
            $propertyType = '?'.$propertyType;
        }

        // the constructor asks for the value of a field, unless this value is generated;
        // it has to be given one when the field is neither nullable nor an array
        $isPromoted = $mapping->promoted && !($mapping->id ?? false) && $this->addPromotedProperty(
            name: $mapping->propertyName,
            propertyType: $nullable ? $propertyType : $typeHint,
            defaultValue: $nullable ? null : $defaultValue ?? self::DEFAULT_VALUE_NONE,
            attributes: $attributes,
            comments: $mapping->comments
        );

        if (!$isPromoted) {
            $this->addProperty(
                name: $mapping->propertyName,
                defaultValue: $defaultValue,
                attributes: $attributes,
                comments: $mapping->comments,
                propertyType: $propertyType
            );
        }

        // a property that already exists is never overwritten, hence reading it from the class
        $isAlwaysSet = $this->isNonNullablePromotedProperty($mapping->propertyName);

        $this->addGetter(
            $mapping->propertyName,
            $typeHint,
            // getter methods always have nullable return values
            // because even though these are required in the db, they may not be set yet
            // unless there is a default value or the constructor sets them
            null === $defaultValue && 'mixed' !== $propertyType && !$isAlwaysSet,
            $commentLines
        );

        // don't generate setters for id fields
        if (!($mapping->id ?? false)) {
            $this->addSetter($mapping->propertyName, $typeHint, $nullable && 'mixed' !== $propertyType && !$isAlwaysSet);
        }
    }

    public function addEmbeddedEntity(string $propertyName, string $className): void
    {
        $typeHint = $this->addUseStatementIfNecessary($className);

        $attributes = [
            $this->buildAttributeNode(
                Embedded::class,
                ['class' => new ClassNameValue($className, $typeHint)],
                'ORM'
            ),
        ];

        $this->addProperty(
            name: $propertyName,
            attributes: $attributes,
            propertyType: $typeHint,
        );

        // logic to avoid re-adding the same ArrayCollection line
        $addEmbedded = true;
        if ($this->getConstructorNode()) {
            // We print the constructor to a string, then
            // look for "$this->propertyName = "

            $constructorString = $this->printer->prettyPrint([$this->getConstructorNode()]);
            if (str_contains($constructorString, \sprintf('$this->%s = ', $propertyName))) {
                $addEmbedded = false;
            }
        }

        // the constructor already sets a property that it promotes
        if ($addEmbedded && !$this->getPromotedPropertyNode($propertyName)) {
            $this->addStatementToConstructor(
                new Node\Stmt\Expression(new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName),
                    new Node\Expr\New_(new Node\Name($typeHint))
                ))
            );
        }

        $this->addGetter($propertyName, $typeHint, false);
        $this->addSetter($propertyName, $typeHint, false);
    }

    public function addManyToOneRelation(RelationManyToOne $manyToOne): void
    {
        $this->addSingularRelation($manyToOne);
    }

    public function addOneToOneRelation(RelationOneToOne $oneToOne): void
    {
        $this->addSingularRelation($oneToOne);
    }

    public function addOneToManyRelation(RelationOneToMany $oneToMany): void
    {
        $this->addCollectionRelation($oneToMany);
    }

    public function addManyToManyRelation(RelationManyToMany $manyToMany): void
    {
        $this->addCollectionRelation($manyToMany);
    }

    public function addInterface(string $interfaceName): void
    {
        $this->addUseStatementIfNecessary($interfaceName);

        $this->getClassNode()->implements[] = new Node\Name(Str::getShortClassName($interfaceName));
        $this->updateSourceCodeFromNewStmts();
    }

    /**
     * @param string $trait the fully-qualified trait name
     */
    public function addTrait(string $trait): void
    {
        $importedClassName = $this->addUseStatementIfNecessary($trait);

        /** @var Node\Stmt\TraitUse[] $traitNodes */
        $traitNodes = $this->findAllNodes(static fn ($node) => $node instanceof Node\Stmt\TraitUse);

        foreach ($traitNodes as $node) {
            if ($node->traits[0]->toString() === $importedClassName) {
                return;
            }
        }

        $traitNodes[] = new Node\Stmt\TraitUse([new Node\Name($importedClassName)]);

        $classNode = $this->getClassNode();

        if (!empty($classNode->stmts) && 1 === \count($traitNodes)) {
            $traitNodes[] = $this->createBlankLineNode(self::CONTEXT_CLASS);
        }

        // avoid all the use traits in class for unshift all the new UseTrait
        // in the right order.
        foreach ($classNode->stmts as $key => $node) {
            if ($node instanceof Node\Stmt\TraitUse) {
                unset($classNode->stmts[$key]);
            }
        }

        array_unshift($classNode->stmts, ...$traitNodes);

        $this->updateSourceCodeFromNewStmts();
    }

    public function addAccessorMethod(string $propertyName, string $methodName, $returnType, bool $isReturnTypeNullable, array $commentLines = [], $typeCast = null): void
    {
        $this->addCustomGetter($propertyName, $methodName, $returnType, $isReturnTypeNullable, $commentLines, $typeCast);
    }

    public function addGetter(string $propertyName, $returnType, bool $isReturnTypeNullable, array $commentLines = []): void
    {
        $methodName = $this->getGetterName($propertyName, $returnType);
        $this->addCustomGetter($propertyName, $methodName, $returnType, $isReturnTypeNullable, $commentLines);
    }

    private function getGetterName(string $propertyName, $returnType): string
    {
        if ('bool' !== $returnType) {
            return 'get'.Str::asCamelCase($propertyName);
        }

        // exclude is & has from getter definition if already in property name
        if (0 !== strncasecmp($propertyName, 'is', 2) && 0 !== strncasecmp($propertyName, 'has', 3)) {
            return 'is'.Str::asCamelCase($propertyName);
        }

        return Str::asLowerCamelCase($propertyName);
    }

    public function addSetter(string $propertyName, ?string $type, bool $isNullable, array $commentLines = []): void
    {
        $builder = $this->createSetterNodeBuilder($propertyName, $type, $isNullable, $commentLines);
        $builder->addStmt(
            new Node\Stmt\Expression(new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName),
                new Node\Expr\Variable($propertyName)
            ))
        );
        $this->makeMethodFluent($builder);
        $this->addMethod($builder->getNode());
    }

    /**
     * @param Node[] $params
     */
    public function addConstructor(array $params, string $methodBody): void
    {
        if (null !== $this->getConstructorNode()) {
            throw new \LogicException('Constructor already exists.');
        }

        $methodBuilder = $this->createMethodBuilder('__construct', null, false);

        $this->addMethodParams($methodBuilder, $params);

        $this->addMethodBody($methodBuilder, $methodBody);

        $this->addNodeAfterProperties($methodBuilder->getNode());
        $this->updateSourceCodeFromNewStmts();
    }

    /**
     * @param Node[] $params
     */
    public function addMethodBuilder(Builder\Method $methodBuilder, array $params = [], ?string $methodBody = null): void
    {
        $this->addMethodParams($methodBuilder, $params);

        if ($methodBody) {
            $this->addMethodBody($methodBuilder, $methodBody);
        }

        $this->addMethod($methodBuilder->getNode());
    }

    public function addMethodBody(Builder\Method $methodBuilder, string $methodBody): void
    {
        $nodes = $this->parser->parse($methodBody);
        $methodBuilder->addStmts($nodes);
    }

    public function createMethodBuilder(string $methodName, $returnType, bool $isReturnTypeNullable, array $commentLines = []): Builder\Method
    {
        $methodNodeBuilder = (new Builder\Method($methodName))
            ->makePublic();

        if (null !== $returnType) {
            if (class_exists($returnType) || interface_exists($returnType)) {
                $returnType = $this->addUseStatementIfNecessary($returnType);
            }
            $methodNodeBuilder->setReturnType($isReturnTypeNullable ? new Node\NullableType(new Node\Identifier($returnType)) : $returnType);
        }

        if ($commentLines) {
            $methodNodeBuilder->setDocComment($this->createDocBlock($commentLines));
        }

        return $methodNodeBuilder;
    }

    public function createMethodLevelCommentNode(string $comment)
    {
        return $this->createSingleLineCommentNode($comment, self::CONTEXT_CLASS_METHOD);
    }

    public function createMethodLevelBlankLine()
    {
        return $this->createBlankLineNode(self::CONTEXT_CLASS_METHOD);
    }

    /**
     * @param array<Node\Attribute|Node\AttributeGroup> $attributes
     */
    public function addProperty(string $name, $defaultValue = self::DEFAULT_VALUE_NONE, array $attributes = [], array $comments = [], ?string $propertyType = null): void
    {
        if ($this->propertyExists($name)) {
            // we never overwrite properties
            return;
        }

        $newPropertyBuilder = (new Builder\Property($name))->makePrivate();

        if (null !== $propertyType) {
            $newPropertyBuilder->setType($propertyType);
        }

        if ($this->useAttributesForDoctrineMapping) {
            foreach ($attributes as $attribute) {
                $newPropertyBuilder->addAttribute($attribute);
            }
        }

        if ($comments) {
            $newPropertyBuilder->setDocComment($this->createDocBlock($comments));
        }

        if (self::DEFAULT_VALUE_NONE !== $defaultValue) {
            $newPropertyBuilder->setDefault($defaultValue);
        }
        $newPropertyNode = $newPropertyBuilder->getNode();

        $this->addNodeAfterProperties($newPropertyNode);
    }

    public function addAttributeToClass(string $attributeClass, array $options): void
    {
        $this->addUseStatementIfNecessary($attributeClass);

        $classNode = $this->getClassNode();

        $attributePrefix = str_starts_with($attributeClass, 'ORM\\') ? 'ORM' : null;

        $classNode->attrGroups[] = new Node\AttributeGroup([$this->buildAttributeNode($attributeClass, $options, $attributePrefix)]);

        $this->updateSourceCodeFromNewStmts();
    }

    private function addCustomGetter(string $propertyName, string $methodName, $returnType, bool $isReturnTypeNullable, array $commentLines = [], $typeCast = null): void
    {
        $propertyFetch = new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $propertyName);

        if (null !== $typeCast) {
            switch ($typeCast) {
                case 'string':
                    $propertyFetch = new Node\Expr\Cast\String_($propertyFetch);
                    break;
                default:
                    // implement other cases if/when the library needs them
                    throw new \Exception('Not implemented.');
            }
        }

        $getterNodeBuilder = (new Builder\Method($methodName))
            ->makePublic()
            ->addStmt(
                new Node\Stmt\Return_($propertyFetch)
            );

        if (null !== $returnType) {
            $getterNodeBuilder->setReturnType($isReturnTypeNullable ? new Node\NullableType(new Node\Identifier($returnType)) : $returnType);
        }

        if ($commentLines) {
            $getterNodeBuilder->setDocComment($this->createDocBlock($commentLines));
        }

        $this->addMethod($getterNodeBuilder->getNode());
    }

    private function createSetterNodeBuilder(string $propertyName, $type, bool $isNullable, array $commentLines = []): Builder\Method
    {
        $methodName = $this->getSetterName($propertyName, $type);
        $setterNodeBuilder = (new Builder\Method($methodName))->makePublic();

        if ($commentLines) {
            $setterNodeBuilder->setDocComment($this->createDocBlock($commentLines));
        }

        $paramBuilder = new Builder\Param($propertyName);
        if (null !== $type) {
            $paramBuilder->setType($isNullable ? new Node\NullableType(new Node\Identifier($type)) : $type);
        }
        $setterNodeBuilder->addParam($paramBuilder->getNode());

        return $setterNodeBuilder;
    }

    private function getSetterName(string $propertyName, $type): string
    {
        return 'set'.Str::asCamelCase($propertyName);
    }

    private function addSingularRelation(BaseRelation $relation): void
    {
        $typeHint = $this->addUseStatementIfNecessary($relation->getTargetClassName());
        if ($relation->getTargetClassName() === $this->getThisFullClassName()) {
            $typeHint = 'self';
        }

        $annotationOptions = [];
        if ($relation->isOwning()) {
            // sometimes, we don't map the inverse relation
            if ($relation->getMapInverseRelation()) {
                $annotationOptions['inversedBy'] = $relation->getTargetPropertyName();
            }
        } else {
            $annotationOptions['mappedBy'] = $relation->getTargetPropertyName();
        }

        if ('self' === $typeHint) {
            // Doctrine does not currently resolve "self" correctly for targetEntity guessing
            $annotationOptions['targetEntity'] = new ClassNameValue($typeHint, $relation->getTargetClassName());
        }

        if ($relation instanceof RelationOneToOne) {
            $annotationOptions['cascade'] = ['persist', 'remove'];
        }

        $attributes = [
            $this->buildAttributeNode(
                $relation instanceof RelationManyToOne ? ManyToOne::class : OneToOne::class,
                $annotationOptions,
                'ORM'
            ),
        ];

        if (!$relation->isNullable() && $relation->isOwning()) {
            $attributes[] = $this->buildAttributeNode(JoinColumn::class, ['nullable' => false], 'ORM');
        }

        // the constructor asks for the object of a relation, which
        // it has to be given when the relation is not nullable
        $isPromoted = $relation->isOwningSidePromoted() && $relation->isOwning() && $this->addPromotedProperty(
            name: $relation->getPropertyName(),
            propertyType: ($relation->isNullable() ? '?' : '').$typeHint,
            defaultValue: $relation->isNullable() ? null : self::DEFAULT_VALUE_NONE,
            attributes: $attributes
        );

        if (!$isPromoted) {
            $this->addProperty(
                name: $relation->getPropertyName(),
                defaultValue: null,
                attributes: $attributes,
                propertyType: '?'.$typeHint,
            );
        }

        // a property that already exists is never overwritten, hence reading it from the class
        $isAlwaysSet = $this->isNonNullablePromotedProperty($relation->getPropertyName());

        $this->addGetter(
            $relation->getPropertyName(),
            $relation->getCustomReturnType() ?? $typeHint,
            // getter methods always have nullable return values, unless this
            // has been customized explicitly or the constructor sets the property
            $relation->getCustomReturnType() ? $relation->isCustomReturnTypeNullable() : !$isAlwaysSet
        );

        if ($relation->shouldAvoidSetter()) {
            return;
        }

        $setterNodeBuilder = $this->createSetterNodeBuilder(
            $relation->getPropertyName(),
            $typeHint,
            // make the type-hint nullable always for ManyToOne to allow the owning
            // side to be set to null, which is needed for orphanRemoval
            // (specifically: when you set the inverse side, the generated
            // code will *also* set the owning side to null - so it needs to be allowed)
            // e.g. $userAvatarPhoto->setUser(null);
            // unless the constructor sets the property, which cannot be unset then
            !$isAlwaysSet && (!$relation instanceof RelationOneToOne || $relation->isNullable())
        );

        // set the *owning* side of the relation
        // OneToOne is the only "singular" relation type that
        // may be the inverse side
        if ($relation instanceof RelationOneToOne && !$relation->isOwning()) {
            $this->addNodesToSetOtherSideOfOneToOne($relation, $setterNodeBuilder);
        }

        $setterNodeBuilder->addStmt(
            new Node\Stmt\Expression(new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $relation->getPropertyName()),
                new Node\Expr\Variable($relation->getPropertyName())
            ))
        );
        $this->makeMethodFluent($setterNodeBuilder);
        $this->addMethod($setterNodeBuilder->getNode());
    }

    private function addCollectionRelation(BaseCollectionRelation $relation): void
    {
        $typeHint = $relation->isSelfReferencing() ? 'self' : $this->addUseStatementIfNecessary($relation->getTargetClassName());

        $arrayCollectionTypeHint = $this->addUseStatementIfNecessary(ArrayCollection::class);
        $collectionTypeHint = $this->addUseStatementIfNecessary(Collection::class);

        $annotationOptions = [
            'targetEntity' => new ClassNameValue($typeHint, $relation->getTargetClassName()),
        ];
        if ($relation->isOwning()) {
            // sometimes, we don't map the inverse relation
            if ($relation->getMapInverseRelation()) {
                $annotationOptions['inversedBy'] = $relation->getTargetPropertyName();
            }
        } else {
            $annotationOptions['mappedBy'] = $relation->getTargetPropertyName();
        }

        if ($relation->getOrphanRemoval()) {
            $annotationOptions['orphanRemoval'] = true;
        }

        $attributes = [
            $this->buildAttributeNode(
                $relation instanceof RelationManyToMany ? ManyToMany::class : OneToMany::class,
                $annotationOptions,
                'ORM'
            ),
        ];

        $this->addProperty(
            name: $relation->getPropertyName(),
            attributes: $attributes,
            // add @var that advertises this as a collection of specific objects
            comments: [\sprintf('@var %s<int, %s>', $collectionTypeHint, $typeHint)],
            propertyType: $collectionTypeHint,
        );

        // logic to avoid re-adding the same ArrayCollection line
        $addArrayCollection = true;
        if ($this->getConstructorNode()) {
            // We print the constructor to a string, then
            // look for "$this->propertyName = "

            $constructorString = $this->printer->prettyPrint([$this->getConstructorNode()]);
            if (str_contains($constructorString, \sprintf('$this->%s = ', $relation->getPropertyName()))) {
                $addArrayCollection = false;
            }
        }

        // the constructor already sets a property that it promotes
        if ($addArrayCollection && !$this->getPromotedPropertyNode($relation->getPropertyName())) {
            $this->addStatementToConstructor(
                new Node\Stmt\Expression(new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $relation->getPropertyName()),
                    new Node\Expr\New_(new Node\Name($arrayCollectionTypeHint))
                ))
            );
        }

        $this->addGetter(
            $relation->getPropertyName(),
            $collectionTypeHint,
            false,
            // add @return that advertises this as a collection of specific objects
            [\sprintf('@return %s<int, %s>', $collectionTypeHint, $typeHint)]
        );

        $argName = Str::pluralCamelCaseToSingular($relation->getPropertyName());

        // adder method
        $adderNodeBuilder = (new Builder\Method($relation->getAdderMethodName()))->makePublic();

        $paramBuilder = new Builder\Param($argName);
        $paramBuilder->setType($typeHint);
        $adderNodeBuilder->addParam($paramBuilder->getNode());

        // if (!$this->avatars->contains($avatar))
        $containsMethodCallNode = new Node\Expr\MethodCall(
            new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $relation->getPropertyName()),
            'contains',
            [new Node\Expr\Variable($argName)]
        );
        $ifNotContainsStmt = new Node\Stmt\If_(
            new Node\Expr\BooleanNot($containsMethodCallNode)
        );
        $adderNodeBuilder->addStmt($ifNotContainsStmt);

        // append the item
        $ifNotContainsStmt->stmts[] = new Node\Stmt\Expression(
            new Node\Expr\MethodCall(
                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $relation->getPropertyName()),
                'add',
                [new Node\Expr\Variable($argName)]
            ));

        // set the owning side of the relationship
        if (!$relation->isOwning()) {
            $ifNotContainsStmt->stmts[] = new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable($argName),
                    $relation->getTargetSetterMethodName(),
                    [new Node\Expr\Variable('this')]
                )
            );
        }

        $this->makeMethodFluent($adderNodeBuilder);
        $this->addMethod($adderNodeBuilder->getNode());

        /*
         * Remover
         */
        $removerNodeBuilder = (new Builder\Method($relation->getRemoverMethodName()))->makePublic();

        $paramBuilder = new Builder\Param($argName);
        $paramBuilder->setType($typeHint);
        $removerNodeBuilder->addParam($paramBuilder->getNode());

        // $this->avatars->removeElement($avatar)
        $removeElementCall = new Node\Expr\MethodCall(
            new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $relation->getPropertyName()),
            'removeElement',
            [new Node\Expr\Variable($argName)]
        );

        // set the owning side of the relationship, unless it is a promoted property
        // that cannot be unset: the object is only removed from the collection then
        if ($relation->isOwning() || ($relation instanceof RelationOneToMany && $relation->isOwningSidePromoted() && !$relation->isNullable())) {
            // $this->avatars->removeElement($avatar);
            $removerNodeBuilder->addStmt(BuilderHelpers::normalizeStmt($removeElementCall));
        } else {
            // if ($this->avatars->removeElement($avatar))
            $ifRemoveElementStmt = new Node\Stmt\If_($removeElementCall);
            $removerNodeBuilder->addStmt($ifRemoveElementStmt);
            if ($relation instanceof RelationOneToMany) {
                // OneToMany: $student->setCourse(null);
                /*
                 * // set the owning side to null (unless already changed)
                 * if ($student->getCourse() === $this) {
                 *     $student->setCourse(null);
                 * }
                 */

                $ifRemoveElementStmt->stmts[] = $this->createSingleLineCommentNode(
                    'set the owning side to null (unless already changed)',
                    self::CONTEXT_CLASS_METHOD
                );

                // if ($student->getCourse() === $this) {
                $ifNode = new Node\Stmt\If_(new Node\Expr\BinaryOp\Identical(
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable($argName),
                        $relation->getTargetGetterMethodName()
                    ),
                    new Node\Expr\Variable('this')
                ));

                // $student->setCourse(null);
                $ifNode->stmts = [
                    new Node\Stmt\Expression(new Node\Expr\MethodCall(
                        new Node\Expr\Variable($argName),
                        $relation->getTargetSetterMethodName(),
                        [new Node\Arg($this->createNullConstant())]
                    )),
                ];

                $ifRemoveElementStmt->stmts[] = $ifNode;
            } elseif ($relation instanceof RelationManyToMany) {
                // $student->removeCourse($this);
                $ifRemoveElementStmt->stmts[] = new Node\Stmt\Expression(
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable($argName),
                        $relation->getTargetRemoverMethodName(),
                        [new Node\Expr\Variable('this')]
                    )
                );
            } else {
                throw new \Exception('Unknown relation type.');
            }
        }

        $this->makeMethodFluent($removerNodeBuilder);
        $this->addMethod($removerNodeBuilder->getNode());
    }

    private function addStatementToConstructor(Node\Stmt $stmt): void
    {
        $constructorNode = $this->getConstructorNode() ?? $this->createConstructorNode();

        if ([] !== $constructorNode->stmts) {
            $constructorNode->stmts[] = $stmt;
            $this->updateSourceCodeFromNewStmts();

            return;
        }

        // PHP-Parser can only add a statement to an empty body by printing the whole method
        // again, which loses the format of its signature: the source code is edited instead
        [$indentation, $innerIndentation] = $this->getConstructorIndentations($constructorNode);
        $bodyStart = $this->getConstructorPositions($constructorNode)[2] + 1;
        $code = str_replace("\n", "\n".$innerIndentation, $this->printer->prettyPrint([$stmt]));

        $this->setSourceCode(substr_replace(
            $this->sourceCode,
            "\n".$innerIndentation.$code."\n".$indentation,
            $bodyStart,
            $constructorNode->getEndFilePos() - $bodyStart
        ));
    }

    private function createConstructorNode(): Node\Stmt\ClassMethod
    {
        $constructorNode = (new Builder\Method('__construct'))->makePublic()->getNode();

        // add call to parent::__construct() if there is a need to
        try {
            $ref = new \ReflectionClass($this->getThisFullClassName());

            if ($ref->getParentClass() && $ref->getParentClass()->getConstructor()) {
                $constructorNode->stmts[] = new Node\Stmt\Expression(
                    new Node\Expr\StaticCall(new Node\Name('parent'), new Node\Identifier('__construct'))
                );
            }
        } catch (\ReflectionException) {
        }

        $this->addNodeAfterProperties($constructorNode);

        return $this->getConstructorNode();
    }

    /**
     * Adds a property as a promoted parameter of the constructor, which is created when the class has none.
     *
     * @param array<Node\Attribute|Node\AttributeGroup> $attributes
     *
     * @return bool false when the constructor cannot take the parameter
     */
    private function addPromotedProperty(string $name, ?string $propertyType, $defaultValue = self::DEFAULT_VALUE_NONE, array $attributes = [], array $comments = []): bool
    {
        if ($this->propertyExists($name)) {
            // we never overwrite properties
            return true;
        }

        if ($constructorNode = $this->getConstructorNode()) {
            // an abstract constructor has no body to set the property in
            if (null === $constructorNode->stmts) {
                return false;
            }

            foreach ($constructorNode->params as $param) {
                if ($param->var instanceof Node\Expr\Variable && $name === $param->var->name) {
                    return false;
                }
            }
        }

        $paramBuilder = (new Builder\Param($name))->makePrivate();

        if (null !== $propertyType) {
            $paramBuilder->setType($propertyType);
        }

        if ($isOptional = self::DEFAULT_VALUE_NONE !== $defaultValue) {
            $paramBuilder->setDefault($defaultValue);
        }

        // the comments, then each attribute, then the parameter: all on their own line
        $lines = $comments ? explode("\n", (new Doc($this->createDocBlock($comments)))->getReformattedText()) : [];

        if ($this->useAttributesForDoctrineMapping) {
            foreach ($attributes as $attribute) {
                $lines[] = $this->printer->prettyPrint([BuilderHelpers::normalizeAttribute($attribute)]);
            }
        }

        $lines[] = $this->printer->prettyPrint([$paramBuilder->getNode()]);

        $this->addConstructorParameter($constructorNode ?? $this->createConstructorNode(), $lines, $isOptional);

        return true;
    }

    /**
     * Adds a parameter to the constructor, the signature of which
     * ends up with each of its parameters on their own lines.
     *
     * PHP-Parser can only do so by printing the whole method again, which loses
     * the format of its body: the source code is edited instead.
     *
     * @param string[] $lines
     */
    private function addConstructorParameter(Node\Stmt\ClassMethod $constructorNode, array $lines, bool $isOptional): void
    {
        [$indentation, $innerIndentation] = $this->getConstructorIndentations($constructorNode);
        [$openingParenthesis, $closingParenthesis, $openingBrace] = $this->getConstructorPositions($constructorNode);

        $code = implode("\n".$innerIndentation, $lines);
        // an empty line makes it easier to tell apart a parameter that spans several lines
        $separator = ','.(1 < \count($lines) ? "\n\n" : "\n").$innerIndentation;

        $params = $constructorNode->params;

        // a required parameter cannot follow an optional one, and none can follow a variadic one
        $index = \count($params);
        foreach ($params as $i => $param) {
            if ($param->variadic || (!$isOptional && null !== $param->default)) {
                $index = $i;

                break;
            }
        }

        // where each parameter starts, and the code before, between and after the parameters
        $starts = $gaps = [];
        $position = $openingParenthesis + 1;
        foreach ($params as $param) {
            $start = $param->getStartFilePos();
            $lineEnd = strpos(substr($this->sourceCode, $position, $start - $position), "\n");

            // the comments of a parameter belong to it, unlike the one that ends the line of the previous parameter
            foreach ($param->getComments() as $comment) {
                if (false === $lineEnd || $position + $lineEnd < $comment->getStartFilePos()) {
                    $start = $comment->getStartFilePos();

                    break;
                }
            }

            $starts[] = $start;
            $gaps[] = substr($this->sourceCode, $position, $start - $position);
            $position = $param->getEndFilePos() + 1;
        }
        $gaps[] = substr($this->sourceCode, $position, $closingParenthesis - $position);

        // a signature that starts on the line of its parenthesis is written again,
        // as long as there is nothing but its parameters to keep, such as a comment
        if (!str_contains($gaps[0], "\n")
            && '' === trim(implode('', $gaps), ", \t\r\n")
            && '' === trim(substr($this->sourceCode, $closingParenthesis + 1, $openingBrace - $closingParenthesis - 1))
        ) {
            $codes = array_map(fn (Node\Param $param, int $start): string => substr($this->sourceCode, $start, $param->getEndFilePos() + 1 - $start), $params, $starts);
            array_splice($codes, $index, 0, [$code]);

            $list = '';
            foreach ($codes as $i => $paramCode) {
                $list .= match (true) {
                    0 === $i => '',
                    $i === $index, $i - 1 === $index => $separator,
                    default => ",\n".$innerIndentation,
                }.$paramCode;
            }

            // from the opening parenthesis to the opening brace of the body
            $this->setSourceCode(substr_replace(
                $this->sourceCode,
                "(\n".$innerIndentation.$list.",\n".$indentation.') {',
                $openingParenthesis,
                $openingBrace + 1 - $openingParenthesis
            ));

            return;
        }

        // the signature is kept as it is otherwise, and the new parameter
        // is as far from the other ones as they are from each other
        $getSpace = static function (string $gap) use ($separator): string {
            $space = substr($gap, \strlen(rtrim($gap)));

            return str_contains($space, "\n") ? $space : substr($separator, 1);
        };

        if ($index < \count($params)) {
            $this->setSourceCode(substr_replace($this->sourceCode, $code.','.$getSpace($gaps[$index]), $starts[$index], 0));

            return;
        }

        // after the last parameter, and the comment that may follow it
        $comment = rtrim(preg_replace('/^\s*,/', '', $gaps[$index]));
        $before = match ($index) {
            0 => $comment."\n".$innerIndentation,
            1 => ','.$comment.substr($separator, 1),
            default => ','.$comment.$getSpace($gaps[$index - 1]),
        };

        $this->setSourceCode(substr_replace($this->sourceCode, $before.$code.",\n".$indentation, $position, $closingParenthesis - $position));
    }

    /**
     * Returns the positions in the source code of the parentheses around the parameters
     * of the constructor, and the one of the opening brace of its body.
     *
     * @return array{int, int, int}
     */
    private function getConstructorPositions(Node\Stmt\ClassMethod $constructorNode): array
    {
        $positions = [];
        $tokenPosition = $constructorNode->name->getEndTokenPos();

        foreach (['(', ')', '{'] as $text) {
            if (')' === $text && $params = $constructorNode->params) {
                // a parameter may hold parentheses and braces of its own
                $tokenPosition = $params[array_key_last($params)]->getEndTokenPos();
            }

            do {
                $token = $this->oldTokens[++$tokenPosition] ?? throw new \LogicException(\sprintf('Could not find "%s" in the constructor.', $text));
            } while ($text !== $token->text);

            $positions[] = $token->pos;
        }

        return $positions;
    }

    /**
     * Returns the indentation of the constructor, and the one of its parameters and statements.
     *
     * @return array{string, string}
     */
    private function getConstructorIndentations(Node\Stmt\ClassMethod $constructorNode): array
    {
        $getIndentation = function (Node $node): string {
            $code = substr($this->sourceCode, 0, $node->getStartFilePos());

            return substr($code, \strlen(rtrim($code, " \t")));
        };

        $indentation = $getIndentation($constructorNode);
        $classIndentation = $getIndentation($this->getClassNode());

        // what is inside the constructor is one level deeper, a level being what the constructor has more than its class
        $level = str_starts_with($indentation, $classIndentation) ? substr($indentation, \strlen($classIndentation)) : '';

        return [$indentation, $indentation.('' === $level ? '    ' : $level)];
    }

    /**
     * @throws \Exception
     */
    private function getConstructorNode(): ?Node\Stmt\ClassMethod
    {
        foreach ($this->getClassNode()->stmts as $classNode) {
            if ($classNode instanceof Node\Stmt\ClassMethod && '__construct' == $classNode->name) {
                return $classNode;
            }
        }

        return null;
    }

    /**
     * @return string The alias to use when referencing this class
     */
    public function addUseStatementIfNecessary(string $class): string
    {
        $shortClassName = Str::getShortClassName($class);
        if ($this->isInSameNamespace($class)) {
            return $shortClassName;
        }

        $namespaceNode = $this->getNamespaceNode();

        $targetIndex = null;
        $addLineBreak = false;
        $lastUseStmtIndex = null;
        foreach ($namespaceNode->stmts as $index => $stmt) {
            if ($stmt instanceof Node\Stmt\Use_ || $stmt instanceof Node\Stmt\GroupUse) {
                // the names of a grouped use statement are relative to its prefix
                $prefix = $stmt instanceof Node\Stmt\GroupUse ? $stmt->prefix.'\\' : '';

                foreach ($stmt->uses as $use) {
                    $alias = $use->alias ? $use->alias->name : $use->name->getLast();

                    // the use statement already exists? Don't add it again
                    if ($class === $prefix.$use->name) {
                        return $alias;
                    }

                    if (str_starts_with($class, $alias)) {
                        return $class;
                    }

                    if ($alias === $shortClassName) {
                        // we have a conflicting alias!
                        // to be safe, use the fully-qualified class name
                        // everywhere and do not add another use statement
                        return '\\'.$class;
                    }
                }

                // if $class is alphabetically before this use statement, place it before
                // only set $targetIndex the first time you find it
                if (null === $targetIndex && Str::areClassesAlphabetical($class, $prefix.$stmt->uses[0]->name)) {
                    $targetIndex = $index;
                }

                $lastUseStmtIndex = $index;
            } elseif ($stmt instanceof Node\Stmt\Class_) {
                if (null !== $targetIndex) {
                    // we already found where to place the use statement

                    break;
                }

                // we hit the class! If there were any use statements,
                // then put this at the bottom of the use statement list
                if (null !== $lastUseStmtIndex) {
                    $targetIndex = $lastUseStmtIndex + 1;
                } else {
                    $targetIndex = $index;
                    $addLineBreak = true;
                }

                break;
            }
        }

        if (null === $targetIndex) {
            throw new \Exception('Could not find a class!');
        }

        $newUseNode = (new Builder\Use_($class, Node\Stmt\Use_::TYPE_NORMAL))->getNode();
        array_splice(
            $namespaceNode->stmts,
            $targetIndex,
            0,
            $addLineBreak ? [$newUseNode, $this->createBlankLineNode(self::CONTEXT_OUTSIDE_CLASS)] : [$newUseNode]
        );

        $this->updateSourceCodeFromNewStmts();

        return $shortClassName;
    }

    /**
     * Builds a PHPParser attribute node.
     *
     * @param string  $attributeClass  The attribute class which should be used for the attribute E.g. #[Column()]
     * @param array   $options         The named arguments for the attribute ($key = argument name, $value = argument value)
     * @param ?string $attributePrefix If a prefix is provided, the node is built using the prefix. E.g. #[ORM\Column()]
     */
    public function buildAttributeNode(string $attributeClass, array $options, ?string $attributePrefix = null): Node\Attribute
    {
        $options = $this->sortOptionsByClassConstructorParameters($options, $attributeClass);

        $context = $this;
        $nodeArguments = array_map(static function (string $option, mixed $value) use ($context) {
            if (null === $value) {
                return new Node\NullableType(new Node\Identifier($option));
            }

            // Use the Doctrine Types constant
            if ('type' === $option && str_starts_with($value, 'Types::')) {
                return new Node\Arg(
                    new Node\Expr\ConstFetch(new Node\Name($value)),
                    false,
                    false,
                    [],
                    new Node\Identifier($option)
                );
            }

            if ('enumType' === $option) {
                return new Node\Arg(
                    new Node\Expr\ConstFetch(new Node\Name(Str::getShortClassName($value).'::class')),
                    false,
                    false,
                    [],
                    new Node\Identifier($option)
                );
            }

            return new Node\Arg($context->buildNodeExprByValue($value), false, false, [], new Node\Identifier($option));
        }, array_keys($options), array_values($options));

        $class = $attributePrefix ? \sprintf('%s\\%s', $attributePrefix, Str::getShortClassName($attributeClass)) : Str::getShortClassName($attributeClass);

        return new Node\Attribute(
            new Node\Name($class),
            $nodeArguments
        );
    }

    private function updateSourceCodeFromNewStmts(): void
    {
        $newCode = $this->printer->printFormatPreserving(
            $this->newStmts,
            $this->oldStmts,
            $this->oldTokens
        );

        // replace the 3 "fake" items that may be in the code (allowing for different indentation)
        $newCode = preg_replace('/(\ |\t)*private\ \$__EXTRA__LINE;/', '', $newCode);
        $newCode = preg_replace('/use __EXTRA__LINE;/', '', $newCode);
        $newCode = preg_replace('/(\ |\t)*\$__EXTRA__LINE;/', '', $newCode);

        // process comment lines
        foreach ($this->pendingComments as $i => $comment) {
            // sanity check
            $placeholder = \sprintf('$__COMMENT__VAR_%d;', $i);
            if (!str_contains($newCode, $placeholder)) {
                // this can happen if a comment is createSingleLineCommentNode()
                // is called, but then that generated code is ultimately not added
                continue;
            }

            $newCode = str_replace($placeholder, '// '.$comment, $newCode);
        }
        $this->pendingComments = [];

        $this->setSourceCode($newCode);
    }

    private function setSourceCode(string $sourceCode): void
    {
        $this->sourceCode = $sourceCode;
        $this->oldStmts = $this->parser->parse($sourceCode);

        $this->oldTokens = $this->parser->getTokens();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NodeVisitor\CloningVisitor());
        $traverser->addVisitor(new NodeVisitor\NameResolver(null, [
            'replaceNodes' => false,
        ]));
        $this->newStmts = $traverser->traverse($this->oldStmts);
    }

    private function getClassNode(): Node\Stmt\Class_
    {
        $node = $this->findFirstNode(static fn ($node) => $node instanceof Node\Stmt\Class_);

        if (!$node) {
            throw new \Exception('Could not find class node.');
        }

        return $node;
    }

    private function getNamespaceNode(): Node\Stmt\Namespace_
    {
        $node = $this->findFirstNode(static fn ($node) => $node instanceof Node\Stmt\Namespace_);

        if (!$node) {
            throw new \Exception('Could not find namespace node.');
        }

        return $node;
    }

    private function findFirstNode(callable $filterCallback): ?Node
    {
        $traverser = new NodeTraverser();
        $visitor = new NodeVisitor\FirstFindingVisitor($filterCallback);
        $traverser->addVisitor($visitor);
        $traverser->traverse($this->newStmts);

        return $visitor->getFoundNode();
    }

    private function findLastNode(callable $filterCallback, array $ast): ?Node
    {
        $traverser = new NodeTraverser();
        $visitor = new NodeVisitor\FindingVisitor($filterCallback);
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $nodes = $visitor->getFoundNodes();
        $node = end($nodes);

        return false === $node ? null : $node;
    }

    /**
     * @return Node[]
     */
    private function findAllNodes(callable $filterCallback): array
    {
        $traverser = new NodeTraverser();
        $visitor = new NodeVisitor\FindingVisitor($filterCallback);
        $traverser->addVisitor($visitor);
        $traverser->traverse($this->newStmts);

        return $visitor->getFoundNodes();
    }

    private function createBlankLineNode(string $context): Node\Stmt\Use_|Node|Node\Stmt\Property|Node\Expr\Variable
    {
        return match ($context) {
            self::CONTEXT_OUTSIDE_CLASS => (new Builder\Use_(
                '__EXTRA__LINE',
                Node\Stmt\Use_::TYPE_NORMAL
            ))
                ->getNode(),
            self::CONTEXT_CLASS => (new Builder\Property('__EXTRA__LINE'))
                ->makePrivate()
                ->getNode(),
            self::CONTEXT_CLASS_METHOD => new Node\Expr\Variable(
                '__EXTRA__LINE'
            ),
            default => throw new \Exception('Unknown context: '.$context),
        };
    }

    private function createSingleLineCommentNode(string $comment, string $context): Node\Stmt
    {
        $this->pendingComments[] = $comment;
        switch ($context) {
            case self::CONTEXT_OUTSIDE_CLASS:
                // just not needed yet
                throw new \Exception('Not supported.');
            case self::CONTEXT_CLASS:
                // just not needed yet
                throw new \Exception('Not supported.');
            case self::CONTEXT_CLASS_METHOD:
                return BuilderHelpers::normalizeStmt(new Node\Expr\Variable(\sprintf('__COMMENT__VAR_%d', \count($this->pendingComments) - 1)));
            default:
                throw new \Exception('Unknown context: '.$context);
        }
    }

    private function createDocBlock(array $commentLines): string
    {
        $docBlock = "/**\n";
        foreach ($commentLines as $commentLine) {
            if ($commentLine) {
                $docBlock .= " * $commentLine\n";
            } else {
                // avoid the empty, extra space on blank lines
                $docBlock .= " *\n";
            }
        }
        $docBlock .= "\n */";

        return $docBlock;
    }

    private function addMethod(Node\Stmt\ClassMethod $methodNode): void
    {
        $classNode = $this->getClassNode();
        $methodName = $methodNode->name;
        $existingIndex = null;
        if ($this->methodExists($methodName)) {
            if (!$this->overwrite) {
                $this->writeNote(\sprintf(
                    'Not generating <info>%s::%s()</info>: method already exists',
                    Str::getShortClassName($this->getThisFullClassName()),
                    $methodName
                ));

                return;
            }

            // record, so we can overwrite in the same place
            $existingIndex = $this->getMethodIndex($methodName);
        }

        $newStatements = [];

        // put new method always at the bottom
        if (!empty($classNode->stmts)) {
            $newStatements[] = $this->createBlankLineNode(self::CONTEXT_CLASS);
        }

        $newStatements[] = $methodNode;

        if (null === $existingIndex) {
            // add them to the end!

            $classNode->stmts = array_merge($classNode->stmts, $newStatements);
        } else {
            array_splice(
                $classNode->stmts,
                $existingIndex,
                1,
                $newStatements
            );
        }

        $this->updateSourceCodeFromNewStmts();
    }

    private function makeMethodFluent(Builder\Method $methodBuilder): void
    {
        $methodBuilder
            ->addStmt($this->createBlankLineNode(self::CONTEXT_CLASS_METHOD))
            ->addStmt(new Node\Stmt\Return_(new Node\Expr\Variable('this')));
        $methodBuilder->setReturnType('static');
    }

    private function isInSameNamespace(string $class): bool
    {
        $namespace = substr($class, 0, strrpos($class, '\\'));

        return $this->getNamespaceNode()->name->toCodeString() === $namespace;
    }

    private function getThisFullClassName(): string
    {
        return (string) $this->getClassNode()->namespacedName;
    }

    /**
     * Adds this new node where a new property should go.
     *
     * Useful for adding properties, or adding a constructor.
     */
    private function addNodeAfterProperties(Node $newNode): void
    {
        $classNode = $this->getClassNode();

        // try to add after last property
        $targetNode = $this->findLastNode(static fn ($node) => $node instanceof Node\Stmt\Property, [$classNode]);

        // otherwise, try to add after the last constant
        if (!$targetNode) {
            $targetNode = $this->findLastNode(static fn ($node) => $node instanceof Node\Stmt\ClassConst, [$classNode]);
        }

        // otherwise, try to add after the last trait
        if (!$targetNode) {
            $targetNode = $this->findLastNode(static fn ($node) => $node instanceof Node\Stmt\TraitUse, [$classNode]);
        }

        // add the new property after this node
        if ($targetNode) {
            $index = array_search($targetNode, $classNode->stmts);

            array_splice(
                $classNode->stmts,
                $index + 1,
                0,
                [$this->createBlankLineNode(self::CONTEXT_CLASS), $newNode]
            );

            $this->updateSourceCodeFromNewStmts();

            return;
        }

        // put right at the beginning of the class
        // add an empty line, unless the class is totally empty
        if (!empty($classNode->stmts)) {
            array_unshift($classNode->stmts, $this->createBlankLineNode(self::CONTEXT_CLASS));
        }
        array_unshift($classNode->stmts, $newNode);
        $this->updateSourceCodeFromNewStmts();
    }

    private function createNullConstant(): Node\Expr\ConstFetch
    {
        return new Node\Expr\ConstFetch(new Node\Name('null'));
    }

    private function addNodesToSetOtherSideOfOneToOne(RelationOneToOne $relation, Builder\Method $setterNodeBuilder): void
    {
        if (!$relation->isNullable()) {
            $setterNodeBuilder->addStmt($this->createSingleLineCommentNode(
                'set the owning side of the relation if necessary',
                self::CONTEXT_CLASS_METHOD
            ));

            // if ($user->getUserProfile() !== $this) {
            $ifNode = new Node\Stmt\If_(new Node\Expr\BinaryOp\NotIdentical(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable($relation->getPropertyName()),
                    $relation->getTargetGetterMethodName()
                ),
                new Node\Expr\Variable('this')
            ));

            // $user->setUserProfile($this);
            $ifNode->stmts = [
                new Node\Stmt\Expression(new Node\Expr\MethodCall(
                    new Node\Expr\Variable($relation->getPropertyName()),
                    $relation->getTargetSetterMethodName(),
                    [new Node\Arg(new Node\Expr\Variable('this'))]
                )),
            ];
            $setterNodeBuilder->addStmt($ifNode);
            $setterNodeBuilder->addStmt($this->createBlankLineNode(self::CONTEXT_CLASS_METHOD));

            return;
        }

        // at this point, we know the relation is nullable
        $setterNodeBuilder->addStmt($this->createSingleLineCommentNode(
            'unset the owning side of the relation if necessary',
            self::CONTEXT_CLASS_METHOD
        ));

        // if ($user !== null && $user->getUserProfile() !== $this)
        $ifNode = new Node\Stmt\If_(new Node\Expr\BinaryOp\BooleanAnd(
            new Node\Expr\BinaryOp\Identical(
                new Node\Expr\Variable($relation->getPropertyName()),
                $this->createNullConstant()
            ),
            new Node\Expr\BinaryOp\NotIdentical(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    $relation->getPropertyName()
                ),
                $this->createNullConstant()
            )
        ));
        $ifNode->stmts = [
            // $this->user->setUserProfile(null)
            new Node\Stmt\Expression(new Node\Expr\MethodCall(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    $relation->getPropertyName()
                ),
                $relation->getTargetSetterMethodName(),
                [new Node\Arg($this->createNullConstant())]
            )),
        ];
        $setterNodeBuilder->addStmt($ifNode);

        $setterNodeBuilder->addStmt($this->createBlankLineNode(self::CONTEXT_CLASS_METHOD));
        $setterNodeBuilder->addStmt($this->createSingleLineCommentNode(
            'set the owning side of the relation if necessary',
            self::CONTEXT_CLASS_METHOD
        ));

        // if ($user === null && $this->user !== null)
        $ifNode = new Node\Stmt\If_(new Node\Expr\BinaryOp\BooleanAnd(
            new Node\Expr\BinaryOp\NotIdentical(
                new Node\Expr\Variable($relation->getPropertyName()),
                $this->createNullConstant()
            ),
            new Node\Expr\BinaryOp\NotIdentical(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable($relation->getPropertyName()),
                    $relation->getTargetGetterMethodName()
                ),
                new Node\Expr\Variable('this')
            )
        ));
        $ifNode->stmts = [
            new Node\Stmt\Expression(new Node\Expr\MethodCall(
                new Node\Expr\Variable($relation->getPropertyName()),
                $relation->getTargetSetterMethodName(),
                [new Node\Arg(new Node\Expr\Variable('this'))]
            )),
        ];
        $setterNodeBuilder->addStmt($ifNode);

        $setterNodeBuilder->addStmt($this->createBlankLineNode(self::CONTEXT_CLASS_METHOD));
    }

    private function methodExists(string $methodName): bool
    {
        return false !== $this->getMethodIndex($methodName);
    }

    private function getMethodIndex(string $methodName)
    {
        foreach ($this->getClassNode()->stmts as $i => $node) {
            if ($node instanceof Node\Stmt\ClassMethod && strtolower($node->name->toString()) === strtolower($methodName)) {
                return $i;
            }
        }

        return false;
    }

    private function propertyExists(string $propertyName): bool
    {
        foreach ($this->getClassNode()->stmts as $i => $node) {
            if ($node instanceof Node\Stmt\Property && $node->props[0]->name->toString() === $propertyName) {
                return true;
            }
        }

        return null !== $this->getPromotedPropertyNode($propertyName);
    }

    private function getPromotedPropertyNode(string $propertyName): ?Node\Param
    {
        foreach ($this->getConstructorNode()?->params ?? [] as $param) {
            if ($param->isPromoted() && $param->var instanceof Node\Expr\Variable && $propertyName === $param->var->name) {
                return $param;
            }
        }

        return null;
    }

    /**
     * Tells if the constructor always sets the property to something else than null:
     * the accessors of such a property do not deal with null either.
     */
    private function isNonNullablePromotedProperty(string $propertyName): bool
    {
        $param = $this->getPromotedPropertyNode($propertyName);

        // a property without a type accepts anything
        if (null === $param?->type || $param->type instanceof Node\NullableType) {
            return false;
        }

        // null as a default value makes a type nullable
        if ($param->default instanceof Node\Expr\ConstFetch && 'null' === $param->default->name->toLowerString()) {
            return false;
        }

        foreach ($param->type instanceof Node\UnionType ? $param->type->types : [$param->type] as $type) {
            if ($type instanceof Node\Identifier && \in_array($type->toLowerString(), ['null', 'mixed'], true)) {
                return false;
            }
        }

        return true;
    }

    private function writeNote(string $note): void
    {
        if (null !== $this->io) {
            $this->io->text($note);
        }
    }

    private function addMethodParams(Builder\Method $methodBuilder, array $params): void
    {
        foreach ($params as $param) {
            $methodBuilder->addParam($param);
        }
    }

    /**
     * builds a PHPParser Expr Node based on the value given in $value
     * throws an Exception when the given $value is not resolvable by this method.
     *
     * @throws \Exception
     */
    private function buildNodeExprByValue(mixed $value): Node\Expr
    {
        switch (\gettype($value)) {
            case 'string':
                $nodeValue = new Node\Scalar\String_($value);
                break;
            case 'integer':
                $nodeValue = new Node\Scalar\LNumber($value);
                break;
            case 'double':
                $nodeValue = new Node\Scalar\DNumber($value);
                break;
            case 'boolean':
                $nodeValue = new Node\Expr\ConstFetch(new Node\Name($value ? 'true' : 'false'));
                break;
            case 'array':
                $context = $this;
                $arrayItems = array_map(static fn ($key, $value) => new Node\Expr\ArrayItem(
                    $context->buildNodeExprByValue($value),
                    !\is_int($key) ? $context->buildNodeExprByValue($key) : null
                ), array_keys($value), array_values($value));
                $nodeValue = new Node\Expr\Array_($arrayItems, ['kind' => Node\Expr\Array_::KIND_SHORT]);
                break;
            default:
                $nodeValue = null;
        }

        if (null === $nodeValue) {
            if ($value instanceof ClassNameValue) {
                $nodeValue = new Node\Expr\ConstFetch(
                    new Node\Name(
                        \sprintf('%s::class', $value->isSelf() ? 'self' : $value->getShortName())
                    )
                );
            } elseif ($value instanceof \UnitEnum) {
                $nodeValue = new Node\Expr\ClassConstFetch(
                    new Node\Name($this->addUseStatementIfNecessary($value::class)),
                    $value->name
                );
            } else {
                throw new \Exception(\sprintf('Cannot build a node expr for value of type "%s"', \gettype($value)));
            }
        }

        return $nodeValue;
    }

    /**
     * sort the given options based on the constructor parameters for the given $classString
     * this prevents code inspections warnings for IDEs like intellij/phpstorm.
     *
     * option keys that are not found in the constructor will be added at the end of the sorted array
     */
    private function sortOptionsByClassConstructorParameters(array $options, string $classString): array
    {
        if (str_starts_with($classString, 'ORM\\')) {
            $classString = \sprintf('Doctrine\\ORM\\Mapping\\%s', substr($classString, 4));
        }

        $constructorParameterNames = array_map(static fn (\ReflectionParameter $reflectionParameter) => $reflectionParameter->getName(), (new \ReflectionClass($classString))->getConstructor()->getParameters());

        $sorted = [];
        foreach ($constructorParameterNames as $name) {
            if (\array_key_exists($name, $options)) {
                $sorted[$name] = $options[$name];
                unset($options[$name]);
            }
        }

        return array_merge($sorted, $options);
    }
}
