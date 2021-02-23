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

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
class TemplateClassFormatter
{
    private $phpCompatUtil;

    public function __construct(PhpCompatUtil $phpCompatUtil)
    {
        $this->phpCompatUtil = $phpCompatUtil;
    }

    public function generateClassDetailsForTemplate(string $className): TemplateClassDetails
    {
        return new TemplateClassDetails($className, $this->phpCompatUtil->canUseTypedProperties());
    }
}
