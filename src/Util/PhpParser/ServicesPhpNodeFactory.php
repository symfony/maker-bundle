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

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;

/**
 * Credit to https://github.com/migrify/migrify.
 */
final class ServicesPhpNodeFactory
{
    /**
     * @var string
     */
    private const SERVICES_VARIABLE_NAME = 'services';

    /**
     * @var PhpNodeFactory
     */
    private $phpNodeFactory;

    public function __construct(PhpNodeFactory $phpNodeFactory)
    {
        $this->phpNodeFactory = $phpNodeFactory;
    }

    public function createServicesLoadMethodCall(string $serviceKey, $serviceValues): MethodCall
    {
        $servicesVariable = new Variable(self::SERVICES_VARIABLE_NAME);

        $resource = $serviceValues['resource'];

        $args = [];
        $args[] = new Arg(new String_($serviceKey));
        $args[] = new Arg($this->phpNodeFactory->createAbsoluteDirExpr($resource));

        return new MethodCall($servicesVariable, 'load', $args);
    }
}
