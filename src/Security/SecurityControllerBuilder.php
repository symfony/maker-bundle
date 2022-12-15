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

use PhpParser\Builder\Param;
use PhpParser\Lexer\Emulative;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * @internal
 */
final class SecurityControllerBuilder
{
    public function addLoginMethod(ClassSourceManipulator $manipulator): void
    {
        $loginMethodBuilder = $manipulator->createMethodBuilder('login', 'Response', false);

        $loginMethodBuilder->addAttribute($manipulator->buildAttributeNode(Route::class, ['path' => '/login', 'name' => 'app_login']));

        $manipulator->addUseStatementIfNecessary(Response::class);
        $manipulator->addUseStatementIfNecessary(Route::class);
        $manipulator->addUseStatementIfNecessary(AuthenticationUtils::class);

        $loginMethodBuilder->addParam(
            (new Param('authenticationUtils'))->setTypeHint('AuthenticationUtils')
        );

        $manipulator->addMethodBody($loginMethodBuilder, <<<'CODE'
            <?php
            // if ($this->getUser()) {
            //     return $this->redirectToRoute('target_path');
            // }
            CODE
        );
        $loginMethodBuilder->addStmt($manipulator->createMethodLevelBlankLine());
        $manipulator->addMethodBody($loginMethodBuilder, <<<'CODE'
            <?php
            // get the login error if there is one
            $error = $authenticationUtils->getLastAuthenticationError();
            // last username entered by the user
            $lastUsername = $authenticationUtils->getLastUsername();
            CODE
        );
        $loginMethodBuilder->addStmt($manipulator->createMethodLevelBlankLine());
        $manipulator->addMethodBody($loginMethodBuilder, <<<'CODE'
            <?php
            return $this->render(
                'security/login.html.twig',
                [
                    'last_username' => $lastUsername,
                    'error' => $error,
                ]
            );
            CODE
        );
        $manipulator->addMethodBuilder($loginMethodBuilder);
    }

    public function addLogoutMethod(ClassSourceManipulator $manipulator): void
    {
        $logoutMethodBuilder = $manipulator->createMethodBuilder('logout', 'void', false);

        $logoutMethodBuilder->addAttribute($manipulator->buildAttributeNode(Route::class, ['path' => '/logout', 'name' => 'app_logout']));

        $manipulator->addUseStatementIfNecessary(Route::class);
        $manipulator->addMethodBody($logoutMethodBuilder, <<<'CODE'
            <?php
            throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
            CODE
        );
        $manipulator->addMethodBuilder($logoutMethodBuilder);
    }

    public function addFormLoginMethod(ClassSourceManipulator $manipulator, string $loginTemplatePath): void
    {
        $methodBuilder = $manipulator->createMethodBuilder('login', Response::class, false);
        $methodBuilder->addAttribute($manipulator->buildAttributeNode(Route::class, ['path' => '/login', 'name' => 'app_login']));

        $this->addUseStatements($manipulator, [Route::class]);

        $methodBuilder->addParam((new Param('authenticationUtils'))->setType('AuthenticationUtils'));

        $contents = file_get_contents(\dirname(__DIR__).'/Resources/skeleton/security/formLogin/_LoginMethodBody.tpl.php');

//        $lexer = new Emulative([
//            'usedAttributes' => [
//                'comments',
//                'startLine', 'endLine',
//                'startTokenPos', 'endTokenPos',
//            ],
//        ]);

//        $parser = new Php7($lexer);
//
//        $result = $parser->parse($contents);
//
//        $traverser = new NodeTraverser();
//        $traverser->addVisitor(new class extends NodeVisitorAbstract {
//            public function enterNode(\PhpParser\Node $node) {
//                if ($node instanceof Variable && str_starts_with($node->name, 'tpl_')) {
//                    return new String_('ha-ha');
//                }
//            }
//        });
//
//        $x = $traverser->traverse($result);
//        $printer = new Standard();
//        $x = $printer->prettyPrintFile($x);

        // //        dd($x);
//        foreach ($result as $key => $node) {
        // //            array_map(function ($x) {}, $)
//            array_walk_recursive($result, function ($value, $key) {
//                if ($value instanceof Variable) {
//                    dump($value);
//                }
//            });
        // //            dump($key, $node);
//        }
//
//        dd();
//        dd($contents, $result);

        $manipulator->addMethodBody($methodBuilder, $contents, ['template_path' => $loginTemplatePath]);
        $manipulator->addMethodBuilder($methodBuilder);
    }

    public function addJsonLoginMethod(ClassSourceManipulator $manipulator, ClassNameDetails $userClass): void
    {
        $methodBuilder = $manipulator->createMethodBuilder('apiLogin', JsonResponse::class, false);

        $methodBuilder->addAttribute($manipulator->buildAttributeNode(Route::class, ['path' => '/api/login', 'name' => 'app_api_login']));

        $this->addUseStatements($manipulator, [Route::class, $userClass->getFullName(), CurrentUser::class]);

        $methodBuilder->addParam(
            (new Param('user'))
                ->setType(new NullableType($userClass->getShortName()))
                ->addAttribute(new Attribute(new Name('CurrentUser')))
        );

        $manipulator->addMethodBody($methodBuilder, file_get_contents(\dirname(__DIR__).'/Resources/skeleton/security/jsonLogin/_ApiLoginMethodBody.tpl.php'));

        $manipulator->addMethodBuilder($methodBuilder);
    }

    private function addUseStatements(ClassSourceManipulator $manipulator, array $useStatements): void
    {
        foreach ($useStatements as $statement) {
            $manipulator->addUseStatementIfNecessary($statement);
        }
    }
}
