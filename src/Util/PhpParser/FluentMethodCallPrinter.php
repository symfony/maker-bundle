<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util\PhpParser;

use PhpParser\Node\Expr\MethodCall;
use Symfony\Bundle\MakerBundle\Util\PrettyPrinter;

/**
 * Credit to https://github.com/migrify/migrify.
 */
class FluentMethodCallPrinter extends PrettyPrinter
{
    protected function pExpr_MethodCall(MethodCall $methodCall): string
    {
        $printedMethodCall = parent::pExpr_MethodCall($methodCall);

        $nextCallIndentReplacement = ')'.PHP_EOL.'        ->';

        return str_replace(')->', $nextCallIndentReplacement, $printedMethodCall);
    }
}
