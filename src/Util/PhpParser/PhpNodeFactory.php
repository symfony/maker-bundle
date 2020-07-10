<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util\PhpParser;

use PhpParser\BuilderHelpers;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\String_;
use Symfony\Bundle\MakerBundle\Util\PhpServicesCreator;

/**
 * Credit to https://github.com/migrify/migrify.
 */
final class PhpNodeFactory
{
    public function createAssignContainerCallToVariable(string $variableName, string $methodCallName): Assign
    {
        $variable = new Variable($variableName);
        $containerConfiguratorVariable = new Variable(PhpServicesCreator::CONTAINER_CONFIGURATOR_NAME);

        return new Assign($variable, new MethodCall($containerConfiguratorVariable, $methodCallName));
    }

    public function createParameterSetMethodCall(string $parameterName, $value): MethodCall
    {
        $parametersSetMethodCall = new MethodCall(new Variable('parameters'), 'set');
        $parametersSetMethodCall->args[] = new Arg(BuilderHelpers::normalizeValue($parameterName));

        $parameterValue = $this->createParamValue($value);
        $parametersSetMethodCall->args[] = new Arg($parameterValue);

        return $parametersSetMethodCall;
    }

    /**
     * @param mixed[] $arguments
     */
    public function createImportMethodCall(array $arguments): MethodCall
    {
        $containerConfiguratorVariable = new Variable(PhpServicesCreator::CONTAINER_CONFIGURATOR_NAME);
        $methodCall = new MethodCall($containerConfiguratorVariable, 'import');

        $isFirst = true;
        foreach ($arguments as $argument) {
            // first argument is the resource path
            if ($isFirst) {
                $expr = $this->createAbsoluteDirExpr($argument);
                $isFirst = false;
            } else {
                $expr = BuilderHelpers::normalizeValue($argument);
            }

            $methodCall->args[] = new Arg($expr);
        }

        return $methodCall;
    }

    public function createAbsoluteDirExpr($argument): Expr
    {
        if (\is_string($argument)) {
            // preslash with dir
            $argument = '/'.$argument;
        }

        $argumentValue = BuilderHelpers::normalizeValue($argument);

        if ($argumentValue instanceof String_) {
            $argumentValue = new Concat(new Dir(), $argumentValue);
        }

        return $argumentValue;
    }

    private function createParamValue($value): Expr
    {
        $parameterValue = BuilderHelpers::normalizeValue($value);
        if ($parameterValue instanceof Array_) {
            $parameterValue->setAttribute('kind', Array_::KIND_SHORT);
            // super cheap way to make, at least, the next level of array also short
            foreach ($parameterValue->items as $arrayItem) {
                if ($arrayItem->value instanceof Array_) {
                    $arrayItem->value->setAttribute('kind', Array_::KIND_SHORT);
                }
            }
        }

        return $parameterValue;
    }
}
