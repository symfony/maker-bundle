<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Util\ClassSource;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MakerBundle\Test\MakerTestKernel;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassData;

class ClassDataTest extends TestCase
{
    public function testStaticConstructor(): void
    {
        $meta = ClassData::create(MakerBundle::class);

        // Sanity check in case Maker's NS changes
        self::assertSame('Symfony\Bundle\MakerBundle\MakerBundle', MakerBundle::class);

        self::assertSame('MakerBundle', $meta->className);
        self::assertSame('Symfony\Bundle\MakerBundle', $meta->namespace);
        self::assertSame('Symfony\Bundle\MakerBundle\MakerBundle', $meta->fullClassName);
    }

    public function testGetClassDeclaration(): void
    {
        $meta = ClassData::create(MakerBundle::class);

        self::assertSame('final class MakerBundle', $meta->getClassDeclaration());
    }

    public function testIsFinal(): void
    {
        $meta = ClassData::create(MakerBundle::class);

        // Default - isFinal - true
        self::assertSame('final class MakerBundle', $meta->getClassDeclaration());

        // Not Final - isFinal - false
        $meta->setIsFinal(false);
        self::assertSame('class MakerBundle', $meta->getClassDeclaration());
    }

    public function testGetClassDeclarationWithExtends(): void
    {
        $meta = ClassData::create(class: MakerBundle::class, extendsClass: MakerTestKernel::class);

        self::assertSame('final class MakerBundle extends MakerTestKernel', $meta->getClassDeclaration());
    }
}
