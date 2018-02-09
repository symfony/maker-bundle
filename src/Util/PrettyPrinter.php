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

use PhpParser\PrettyPrinter\Standard;
use PhpParser\Node\Stmt;

/**
 * @internal
 */
final class PrettyPrinter extends Standard
{
    /**
     * Overridden to change coding standards.
     *
     * Before:
     *      public function getFoo() : string
     *
     * After
     *      public function getFoo(): string
     */
    protected function pStmt_ClassMethod(Stmt\ClassMethod $node)
    {
        $classMethod = parent::pStmt_ClassMethod($node);

        if ($node->returnType) {
            $classMethod = str_replace(') :', '):', $classMethod);
        }

        return $classMethod;
    }
}
