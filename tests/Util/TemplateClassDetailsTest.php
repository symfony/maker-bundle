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

        self::assertSame(sprintf('use Symfony\Bundle\MakerBundle\MakerBundle;%s', "\n"), $details->getUseStatement());
        self::assertSame(sprintf('    private $makerBundle;%s', "\n"), $details->getPropertyStatement());
        self::assertSame(sprintf('        $this->makerBundle = $makerBundle;%s', "\n"), $details->getConstructorArgument());
        self::assertSame('MakerBundle $makerBundle', $details->getMethodArgument());
        self::assertSame('$this->makerBundle', $details->getProperty());
        self::assertSame('$makerBundle', $details->getVariable());
    }

    public function testDetailsTypedProperties(): void
    {
        $details = new TemplateClassDetails(MakerBundle::class, true);

        self::assertSame(sprintf('    private MakerBundle $makerBundle;%s', "\n"), $details->getPropertyStatement());
    }

    public function testUseStatementWithoutTrailingNewLine(): void
    {
        $details = new TemplateClassDetails(MakerBundle::class, false);

        self::assertSame('use Symfony\Bundle\MakerBundle\MakerBundle;', $details->getUseStatement(false));
    }

    public function testGetPropertyStatementArguments(): void
    {
        $details = new TemplateClassDetails(MakerBundle::class, false);

        self::assertSame('    private $makerBundle;', $details->getPropertyStatement(false));
        self::assertSame(sprintf('private $makerBundle;%s', "\n"), $details->getPropertyStatement(true, false));
        self::assertSame('private $makerBundle;', $details->getPropertyStatement(false, false));
    }

    public function testGetConstructorArgumentArguments(): void
    {
        $details = new TemplateClassDetails(MakerBundle::class, false);

        self::assertSame('        $this->makerBundle = $makerBundle;', $details->getConstructorArgument(false, true));
        self::assertSame(sprintf('$this->makerBundle = $makerBundle;%s', "\n"), $details->getConstructorArgument(true, false));
        self::assertSame('$this->makerBundle = $makerBundle;', $details->getConstructorArgument(false, false));
    }
}
