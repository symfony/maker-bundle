<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MakerBundle\Util\TemplateClassDetails;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class TemplateClassDetailsTest extends TestCase
{
    public function testDetails(): void
    {
        $details = new TemplateClassDetails(MakerBundle::class, false);

        self::assertSame('use Symfony\Bundle\MakerBundle\MakerBundle;', $details->getUseStatement());
        self::assertSame('    private $makerBundle;', $details->getPropertyStatement());
        self::assertSame('MakerBundle $makerBundle', $details->getMethodArgument());
        self::assertSame('$makerBundle', $details->getVariable());
    }

    public function testDetailsTypedProperties(): void
    {
        $details = new TemplateClassDetails(MakerBundle::class, true);

        self::assertSame('private MakerBundle $makerBundle;', $details->getPropertyStatement());
    }
}
