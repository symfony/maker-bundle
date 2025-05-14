<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Security;

use PhpParser\Node;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassProperty;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGrantedContext;

/**
 * Adds logic to implement UserInterface to an existing User class.
 *
 * @internal
 */
final class UserClassBuilder
{
    public function addUserInterfaceImplementation(ClassSourceManipulator $manipulator, UserClassConfiguration $userClassConfig): void
    {
        $manipulator->addInterface(UserInterface::class);

        $this->addUniqueConstraint($manipulator, $userClassConfig);

        $this->addGetUsername($manipulator, $userClassConfig);

        $this->addGetRoles($manipulator, $userClassConfig);

        $this->addPasswordImplementation($manipulator, $userClassConfig);

        if (class_exists(IsGrantedContext::class)) {
            $this->addSerialize($manipulator);
        }

        if (method_exists(UserInterface::class, 'eraseCredentials')) {
            $this->addEraseCredentials($manipulator);
        }
    }

    private function addPasswordImplementation(ClassSourceManipulator $manipulator, UserClassConfiguration $userClassConfig): void
    {
        if (!$userClassConfig->hasPassword()) {
            return;
        }

        $manipulator->addInterface(PasswordAuthenticatedUserInterface::class);

        $this->addGetPassword($manipulator, $userClassConfig);
    }

    private function addGetUsername(ClassSourceManipulator $manipulator, UserClassConfiguration $userClassConfig): void
    {
        if ($userClassConfig->isEntity()) {
            // add entity property
            $manipulator->addEntityField(
                new ClassProperty(
                    propertyName: $userClassConfig->getIdentityPropertyName(),
                    type: 'string',
                    length: 180,
                )
            );
        } else {
            // add normal property
            $manipulator->addProperty(
                name: $userClassConfig->getIdentityPropertyName()
            );

            $manipulator->addGetter(
                $userClassConfig->getIdentityPropertyName(),
                'string',
                true
            );

            $manipulator->addSetter(
                $userClassConfig->getIdentityPropertyName(),
                'string',
                false
            );
        }

        // define getUsername (if it was defined above, this will override)
        $manipulator->addAccessorMethod(
            $userClassConfig->getIdentityPropertyName(),
            'getUserIdentifier',
            'string',
            false,
            [
                'A visual identifier that represents this user.',
                '',
                '@see UserInterface',
            ],
            true
        );
    }

    private function addGetRoles(ClassSourceManipulator $manipulator, UserClassConfiguration $userClassConfig): void
    {
        if ($userClassConfig->isEntity()) {
            // add entity property
            $manipulator->addEntityField(
                new ClassProperty(propertyName: 'roles', type: 'json', comments: ['@var list<string> The user roles'])
            );
        } else {
            // add normal property
            $manipulator->addProperty(
                name: 'roles',
                defaultValue: new Node\Expr\Array_([], ['kind' => Node\Expr\Array_::KIND_SHORT]),
                comments: [
                    '@var list<string> The user roles',
                ]
            );

            $manipulator->addGetter(
                'roles',
                'array',
                false,
            );
        }

        $manipulator->addSetter(
            'roles',
            'array',
            false,
            ['@param list<string> $roles']
        );

        // define getRoles (if it was defined above, this will override)
        $builder = $manipulator->createMethodBuilder(
            'getRoles',
            'array',
            false,
            ['@see UserInterface']
        );

        // $roles = $this->roles
        $builder->addStmt(
            new Node\Stmt\Expression(new Node\Expr\Assign(
                new Node\Expr\Variable('roles'),
                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), 'roles')
            ))
        );
        // comment line
        $builder->addStmt(
            $manipulator->createMethodLevelCommentNode(
                'guarantee every user at least has ROLE_USER'
            )
        );
        // $roles[] = 'ROLE_USER';
        $builder->addStmt(
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\ArrayDimFetch(
                        new Node\Expr\Variable('roles')
                    ),
                    new Node\Scalar\String_('ROLE_USER')
                )
            )
        );
        $builder->addStmt($manipulator->createMethodLevelBlankLine());
        // return array_unique($roles);
        $builder->addStmt(
            new Node\Stmt\Return_(
                new Node\Expr\FuncCall(
                    new Node\Name('array_unique'),
                    [new Node\Expr\Variable('roles')]
                )
            )
        );

        $manipulator->addMethodBuilder($builder);
    }

    private function addGetPassword(ClassSourceManipulator $manipulator, UserClassConfiguration $userClassConfig): void
    {
        if (!$userClassConfig->hasPassword()) {
            // add an empty method only
            $builder = $manipulator->createMethodBuilder(
                'getPassword',
                'string',
                true,
                [
                    'This method can be removed in Symfony 6.0 - is not needed for apps that do not check user passwords.',
                    '',
                    '@see PasswordAuthenticatedUserInterface',
                ]
            );

            $builder->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\ConstFetch(
                        new Node\Name('null')
                    )
                )
            );

            $manipulator->addMethodBuilder($builder);

            return;
        }

        $propertyDocs = '@var string The hashed password';
        if ($userClassConfig->isEntity()) {
            // add entity property
            $manipulator->addEntityField(
                new ClassProperty(propertyName: 'password', type: 'string', comments: [$propertyDocs])
            );
        } else {
            // add normal property
            $manipulator->addProperty(
                name: 'password',
                comments: [$propertyDocs]
            );

            $manipulator->addGetter(
                'password',
                'string',
                true
            );

            $manipulator->addSetter(
                'password',
                'string',
                false
            );
        }

        // define getPassword (if it was defined above, this will override)
        $manipulator->addAccessorMethod(
            'password',
            'getPassword',
            'string',
            true,
            [
                '@see PasswordAuthenticatedUserInterface',
            ]
        );
    }

    private function addEraseCredentials(ClassSourceManipulator $manipulator): void
    {
        // add eraseCredentials: always empty
        $builder = $manipulator->createMethodBuilder(
            'eraseCredentials',
            'void',
            false
        );
        $builder->addAttribute(new Node\Attribute(new Node\Name('\Deprecated')));
        $builder->addStmt(
            $manipulator->createMethodLevelCommentNode(
                '@deprecated, to be removed when upgrading to Symfony 8'
            )
        );

        $manipulator->addMethodBuilder($builder);
    }

    private function addSerialize(ClassSourceManipulator $manipulator): void
    {
        $builder = $manipulator->createMethodBuilder(
            '__serialize',
            'array',
            false,
            [
                'Ensure the session doesn\'t contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.',
            ]
        );

        // $data = (array) $this;
        $builder->addStmt(
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('data'),
                    new Node\Expr\Cast\Array_(
                        new Node\Expr\Variable('this')
                    )
                )
            )
        );

        // $data["\0".self::class."\0password"] = hash('crc32c', $this->password);
        $builder->addStmt(
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\ArrayDimFetch(
                        new Node\Expr\Variable('data'),
                        new Node\Expr\BinaryOp\Concat(
                            new Node\Expr\BinaryOp\Concat(
                                new Node\Scalar\String_("\0", ['kind' => Node\Scalar\String_::KIND_DOUBLE_QUOTED]),
                                new Node\Expr\ClassConstFetch(
                                    new Node\Name('self'),
                                    'class'
                                )
                            ),
                            new Node\Scalar\String_("\0password", ['kind' => Node\Scalar\String_::KIND_DOUBLE_QUOTED]),
                        )
                    ),
                    new Node\Expr\FuncCall(
                        new Node\Name('hash'),
                        [
                            new Node\Arg(new Node\Scalar\String_('crc32c')),
                            new Node\Arg(
                                new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'password'
                                )
                            ),
                        ]
                    )
                )
            )
        );

        $builder->addStmt(new Node\Stmt\Nop());

        // return $data;
        $builder->addStmt(
            new Node\Stmt\Return_(
                new Node\Expr\Variable('data')
            )
        );

        $manipulator->addMethodBuilder($builder);
    }

    private function addUniqueConstraint(ClassSourceManipulator $manipulator, UserClassConfiguration $userClassConfig): void
    {
        if (!$userClassConfig->isEntity()) {
            return;
        }

        $manipulator->addAttributeToClass(
            'ORM\\UniqueConstraint',
            [
                'name' => 'UNIQ_IDENTIFIER_'.strtoupper(Str::asSnakeCase($userClassConfig->getIdentityPropertyName())),
                'fields' => [$userClassConfig->getIdentityPropertyName()],
            ]
        );
    }
}
