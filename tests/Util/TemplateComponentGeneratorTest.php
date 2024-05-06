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
use Symfony\Bundle\MakerBundle\Util\TemplateComponentGenerator;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class TemplateComponentGeneratorTest extends TestCase
{
    public function testRouteAttributes(): void
    {
        $generator = new TemplateComponentGenerator(false, false);

        $expected = "    #[Route('/', name: 'app_home')]\n";

        self::assertSame($expected, $generator->generateRouteForControllerMethod('/', 'app_home'));
    }

    /**
     * @dataProvider routeMethodDataProvider
     */
    public function testRouteMethods(string $expected, array $methods): void
    {
        $generator = new TemplateComponentGenerator(false, false);

        self::assertSame($expected, $generator->generateRouteForControllerMethod(
            '/',
            'app_home',
            $methods
        ));
    }

    public function routeMethodDataProvider(): \Generator
    {
        yield ["    #[Route('/', name: 'app_home', methods: ['GET'])]\n", ['GET']];
        yield ["    #[Route('/', name: 'app_home', methods: ['GET', 'POST'])]\n", ['GET', 'POST']];
    }

    /**
     * @dataProvider routeIndentationDataProvider
     */
    public function testRouteIndentation(string $expected): void
    {
        $generator = new TemplateComponentGenerator(false, false);

        self::assertSame($expected, $generator->generateRouteForControllerMethod(
            '/',
            'app_home',
            [],
            false
        ));
    }

    public function routeIndentationDataProvider(): \Generator
    {
        yield ["#[Route('/', name: 'app_home')]\n"];
    }

    /**
     * @dataProvider routeTrailingNewLineDataProvider
     */
    public function testRouteTrailingNewLine(string $expected): void
    {
        $generator = new TemplateComponentGenerator(false, false);

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
    }

    /**
     * @dataProvider finalClassDataProvider
     */
    public function testGetFinalClassDeclaration(bool $finalClass, bool $finalEntity, bool $isEntity, string $expectedResult): void
    {
        $this->markTestIncomplete('We wont need this...');
        $generator = new TemplateComponentGenerator($finalClass, $finalEntity);

        self::assertSame($expectedResult, $generator->getFinalDeclaration($isEntity));
    }

    public function finalClassDataProvider(): \Generator
    {
        yield 'Not Final Class' => [false, false, false, ''];
        yield 'Not Final Class w/ Entity' => [false, true, false, ''];
        yield 'Final Class' => [true, false, false, 'final '];
        yield 'Final Class w/ Entity' => [true, true, false, 'final '];
        yield 'Not Final Entity' => [false, false, true, ''];
        yield 'Not Final Entity w/ Class' => [true, false, true, ''];
        yield 'Final Entity' => [false, true, true, 'final '];
        yield 'Final Entity w/ Class' => [true, true, true, 'final '];
    }
}
