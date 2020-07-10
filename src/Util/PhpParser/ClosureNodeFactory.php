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

use PhpParser\Builder\Param;
use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Identifier;
use Symfony\Bundle\MakerBundle\Util\PhpServicesCreator;

/**
 * Credit to https://github.com/migrify/migrify.
 */
final class ClosureNodeFactory
{
    /**
     * @param Node[] $stmts
     */
    public function createClosureFromStmts(array $stmts): Closure
    {
        $paramBuilder = new Param(PhpServicesCreator::CONTAINER_CONFIGURATOR_NAME);
        $paramBuilder->setType('ContainerConfigurator');

        $kernelParamBuilder = new Param('kernel');
        $kernelParamBuilder->setType('Kernel');

        $closure = new Closure([
            'params' => [$paramBuilder->getNode(), $kernelParamBuilder->getNode()],
            'stmts' => $stmts,
        ]);

        $closure->returnType = new Identifier('void');

        return $closure;
    }
}
