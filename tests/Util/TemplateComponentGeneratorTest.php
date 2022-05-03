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
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Bundle\MakerBundle\Util\TemplateComponentGenerator;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class TemplateComponentGeneratorTest extends TestCase
{
    public function testRouteAttributes(): void
    {
        $mockPhpCompatUtil = $this->createMock(PhpCompatUtil::class);
        $mockPhpCompatUtil
            ->expects(self::once())
            ->method('canUseAttributes')
            ->willReturn(true)
        ;

        $generator = new TemplateComponentGenerator($mockPhpCompatUtil);

        $expected = "    #[Route('/', name: 'app_home')]\n";

        self::assertSame($expected, $generator->generateRouteForControllerMethod('/', 'app_home'));
    }

    public function testRouteAnnotations(): void
    {
        $mockPhpCompatUtil = $this->createMock(PhpCompatUtil::class);
        $mockPhpCompatUtil
            ->expects(self::once())
            ->method('canUseAttributes')
            ->willReturn(false)
        ;

        $generator = new TemplateComponentGenerator($mockPhpCompatUtil);

        $expected = "    /**\n";
        $expected .= "     * @Route(\"/\", name=\"app_home\")\n";
        $expected .= "     */\n";

        self::assertSame($expected, $generator->generateRouteForControllerMethod('/', 'app_home'));
    }

    /**
     * @dataProvider routeMethodDataProvider
     */
    public function testRouteMethods(string $expected, bool $useAttribute, array $methods): void
    {
        $mockPhpCompatUtil = $this->createMock(PhpCompatUtil::class);
        $mockPhpCompatUtil
            ->expects(self::once())
            ->method('canUseAttributes')
            ->willReturn($useAttribute)
        ;

        $generator = new TemplateComponentGenerator($mockPhpCompatUtil);

        if (!$useAttribute) {
            $annotation = "    /**\n";
            $annotation .= $expected;
            $annotation .= "     */\n";

            $expected = $annotation;
        }

        self::assertSame($expected, $generator->generateRouteForControllerMethod(
            '/',
            'app_home',
            $methods
        ));
    }

    public function routeMethodDataProvider(): \Generator
    {
        yield ["    #[Route('/', name: 'app_home', methods: ['GET'])]\n", true, ['GET']];
        yield ["     * @Route(\"/\", name=\"app_home\", methods={\"GET\"})\n", false, ['GET']];
        yield ["    #[Route('/', name: 'app_home', methods: ['GET', 'POST'])]\n", true, ['GET', 'POST']];
        yield ["     * @Route(\"/\", name=\"app_home\", methods={\"GET\", \"POST\"})\n", false, ['GET', 'POST']];
    }

    /**
     * @dataProvider routeIndentationDataProvider
     */
    public function testRouteIndentation(string $expected, bool $useAttribute): void
    {
        $mockPhpCompatUtil = $this->createMock(PhpCompatUtil::class);
        $mockPhpCompatUtil
            ->expects(self::once())
            ->method('canUseAttributes')
            ->willReturn($useAttribute)
        ;

        $generator = new TemplateComponentGenerator($mockPhpCompatUtil);

        self::assertSame($expected, $generator->generateRouteForControllerMethod(
            '/',
            'app_home',
            [],
            false
        ));
    }

    public function routeIndentationDataProvider(): \Generator
    {
        yield ["#[Route('/', name: 'app_home')]\n", true];
        yield ["/**\n * @Route(\"/\", name=\"app_home\")\n */\n", false];
    }

    /**
     * @dataProvider routeTrailingNewLineDataProvider
     */
    public function testRouteTrailingNewLine(string $expected, bool $useAttribute): void
    {
        $mockPhpCompatUtil = $this->createMock(PhpCompatUtil::class);
        $mockPhpCompatUtil
            ->expects(self::once())
            ->method('canUseAttributes')
            ->willReturn($useAttribute)
        ;

        $generator = new TemplateComponentGenerator($mockPhpCompatUtil);

        self::assertSame($expected, $generator->generateRouteForControllerMethod(
            '/',
            'app_home',
            [],
            false,
            false
        ));
    }

    public function routeTrailingNewLineDataProvider(): \Generator
    {
        yield ["#[Route('/', name: 'app_home')]", true];
        yield ["/**\n * @Route(\"/\", name=\"app_home\")\n */", false];
    }
}
