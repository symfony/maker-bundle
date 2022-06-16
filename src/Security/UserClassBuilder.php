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
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

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

        $this->addGetUsername($manipulator, $userClassConfig);

        $this->addGetRoles($manipulator, $userClassConfig);

        $this->addPasswordImplementation($manipulator, $userClassConfig);

        $this->addEraseCredentials($manipulator, $userClassConfig);
    }

    private function addPasswordImplementation(ClassSourceManipulator $manipulator, UserClassConfiguration $userClassConfig): void
    {
        // @legacy Drop conditional when Symfony 5.4 is no longer supported
        if (60000 > Kernel::VERSION_ID) {
            // Add methods required to fulfill the UserInterface contract
            $this->addGetPassword($manipulator, $userClassConfig);
            $this->addGetSalt($manipulator, $userClassConfig);
        }

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
                $userClassConfig->getIdentityPropertyName(),
                [
                    'type' => 'string',
                    // https://github.com/FriendsOfSymfony/FOSUserBundle/issues/1919
                    'length' => 180,
                    'unique' => true,
                ]
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

        // @legacy Drop when Symfony 5.4 is no longer supported.
        if (method_exists(UserInterface::class, 'getSalt')) {
            // also add the deprecated getUsername method
            $manipulator->addAccessorMethod(
                $userClassConfig->getIdentityPropertyName(),
                'getUsername',
                'string',
                false,
                ['@deprecated since Symfony 5.3, use getUserIdentifier instead'],
                true
            );
        }
    }

    private function addGetRoles(ClassSourceManipulator $manipulator, UserClassConfiguration $userClassConfig): void
    {
        if ($userClassConfig->isEntity()) {
            // add entity property
            $manipulator->addEntityField(
                'roles',
                [
                    'type' => 'json',
                ]
            );
        } else {
            // add normal property
            $manipulator->addProperty(
                name: 'roles',
                defaultValue: new Node\Expr\Array_([], ['kind' => Node\Expr\Array_::KIND_SHORT])
            );

            $manipulator->addGetter(
                'roles',
                'array',
                false
            );

            $manipulator->addSetter(
                'roles',
                'array',
                false
            );
        }

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
                'password',
                [
                    'type' => 'string',
                ],
                [$propertyDocs]
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
            false,
            [
                '@see PasswordAuthenticatedUserInterface',
            ]
        );
    }

    private function addGetSalt(ClassSourceManipulator $manipulator, UserClassConfiguration $userClassConfig): void
    {
        if ($userClassConfig->hasPassword()) {
            $methodDescription = [
                'Returning a salt is only needed, if you are not using a modern',
                'hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.',
            ];
        } else {
            $methodDescription = [
                'This method can be removed in Symfony 6.0 - is not needed for apps that do not check user passwords.',
            ];
        }

        // add getSalt(): ?string - always returning null
        $builder = $manipulator->createMethodBuilder(
            'getSalt',
            'string',
            true,
            array_merge(
                $methodDescription,
                [
                    '',
                    '@see UserInterface',
                ]
            )
        );

        $builder->addStmt(
            new Node\Stmt\Return_(
                new Node\Expr\ConstFetch(
                    new Node\Name('null')
                )
            )
        );

        $manipulator->addMethodBuilder($builder);
    }

    private function addEraseCredentials(ClassSourceManipulator $manipulator, UserClassConfiguration $userClassConfig): void
    {
        // add eraseCredentials: always empty
        $builder = $manipulator->createMethodBuilder(
            'eraseCredentials',
            null,
            false,
            ['@see UserInterface']
        );
        $builder->addStmt(
            $manipulator->createMethodLevelCommentNode(
                'If you store any temporary, sensitive data on the user, clear it here'
            )
        );
        if ($userClassConfig->hasPassword()) {
            // $this->password = ''
            $builder->addStmt(
                new Node\Stmt\Expression(new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), 'password'),
                    new Node\Scalar\String_('')
                ))
            );
        } else {
            $builder->addStmt(
                $manipulator->createMethodLevelCommentNode(
                    '$this->plainPassword = null;'
                )
            );
        }

        $manipulator->addMethodBuilder($builder);
    }
}
