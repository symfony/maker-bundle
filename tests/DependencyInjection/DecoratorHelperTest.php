<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\DependencyInjection\DecoratorHelper;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceA;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceD;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceE;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceF;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\ServiceInterface;
use Symfony\Bundle\MakerBundle\Tests\DependencyInjection\Fixtures\Sub\ServiceA as SubServiceA;

class DecoratorHelperTest extends TestCase
{
    public function testSuggestIds()
    {
        $this->assertSame([
            'ServiceA',
            'ServiceD',
            'ServiceE',
            'ServiceF',
            'ServiceInterface',
            'bar.service_d',
            'foo.service_e',
            ServiceInterface::class,
            ServiceF::class,
            ServiceA::class,
            SubServiceA::class,
        ], $this->getHelper()->suggestIds());
    }

    /** @dataProvider realIdsProvider */
    public function testGetRealIds(string $id, ?string $expected)
    {
        $this->assertSame($expected, $this->getHelper()->getRealId($id));
    }

    public function realIdsProvider(): \Generator
    {
        yield ['bar.service_d', 'bar.service_d'];
        yield ['foo.service_e', 'foo.service_e'];
        yield [ServiceInterface::class, ServiceInterface::class];
        yield [ServiceF::class, ServiceF::class];
        yield [ServiceA::class, ServiceA::class];
        yield [SubServiceA::class, SubServiceA::class];
        yield ['ServiceA', null];
        yield ['ServiceD', 'bar.service_d'];
        yield ['ServiceE', 'foo.service_e'];
        yield ['ServiceF', ServiceF::class];
        yield ['ServiceInterface', ServiceInterface::class];
        yield ['ServiceeeInterface', null];
        yield ['NotExisting', null];
    }

    /** @dataProvider guessRealIdsProvider */
    public function testGuessRealIds(string $id, array $expected)
    {
        $this->assertSame($expected, $this->getHelper()->guessRealIds($id));
    }

    public function guessRealIdsProvider(): \Generator
    {
        yield [
            'ServiceA',
            [
                ServiceA::class,
                SubServiceA::class,
                'bar.service_d',
                'foo.service_e',
                ServiceF::class,
            ],
        ];

        yield ['ServiceeeInterface', [ServiceInterface::class]];
        yield ['baar.servicce_d', ['bar.service_d']];
        yield ['baaaaaar.servicce_d', []];
        yield ['NotExisting', []];
    }

    /** @dataProvider classProvider */
    public function testGetClass(string $id, string $expected)
    {
        $this->assertSame($expected, $this->getHelper()->getClass($id));
    }

    public function classProvider(): \Generator
    {
        yield ['bar.service_d', ServiceD::class];
        yield ['foo.service_e', ServiceE::class];
        yield [ServiceF::class, ServiceF::class];
        yield [ServiceA::class, ServiceA::class];
        yield [SubServiceA::class, SubServiceA::class];
    }

    public function testInvalidGetClass()
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('Cannot getClass for id "NotExisting".');
        $this->getHelper()->getClass('NotExisting');
    }

    private function getHelper(): DecoratorHelper
    {
        return new DecoratorHelper(
            [
                'bar.service_d',
                'foo.service_e',
                ServiceInterface::class,
                ServiceF::class,
                ServiceA::class,
                SubServiceA::class,
            ], [
                'bar.service_d' => ServiceD::class,
                'foo.service_e' => ServiceE::class,
                ServiceInterface::class => ServiceA::class,
            ], [
                'ServiceA' => [
                    ServiceA::class,
                    SubServiceA::class,
                ],
                'ServiceD' => [
                    'bar.service_d',
                ],
                'ServiceE' => [
                    'foo.service_e',
                ],
                'ServiceF' => [
                    ServiceF::class,
                ],
                'ServiceInterface' => [
                    ServiceInterface::class,
                ],
            ],
        );
    }
}
