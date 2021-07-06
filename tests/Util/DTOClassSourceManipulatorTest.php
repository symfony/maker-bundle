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
use ReflectionMethod;
use Symfony\Bundle\MakerBundle\Util\DTOClassSourceManipulator;

class DTOClassSourceManipulatorTest extends TestCase
{
    /**
     * @dataProvider getSortConstructorParamsTests
     */
    public function testSortConstructorParams(array $params, array $sortedParams)
    {
        $manipulator = new DTOClassSourceManipulator('<?php');
        $sortMethod = new ReflectionMethod(DTOClassSourceManipulator::class, 'sortConstructorParams');
        $sortMethod->setAccessible(true);

        $sortClosure = function (array $paramA, array $paramB) use ($sortMethod, $manipulator) {
            return $sortMethod->invoke($manipulator, $paramA, $paramB);
        };

        usort($params, $sortClosure);

        $this->assertSame($params, $sortedParams);
    }

    public function getSortConstructorParamsTests()
    {
        yield 'sort_nullable' => [
            [
                [
                    'name' => 'a',
                    'nullable' => 'true',
                    'default' => null,
                ],
                [
                    'name' => 'b',
                    'nullable' => false,
                    'default' => null,
                ],
            ],
            [
                [
                    'name' => 'b',
                    'nullable' => false,
                    'default' => null,
                ],
                [
                    'name' => 'a',
                    'nullable' => 'true',
                    'default' => null,
                ],
            ],
        ];

        yield 'sort_default' => [
            [
                [
                    'name' => 'a',
                    'nullable' => false,
                    'default' => [],
                ],
                [
                    'name' => 'b',
                    'nullable' => false,
                    'default' => null,
                ],
            ],
            [
                [
                    'name' => 'b',
                    'nullable' => false,
                    'default' => null,
                ],
                [
                    'name' => 'a',
                    'nullable' => false,
                    'default' => [],
                ],
            ],
        ];

        yield 'sort_nullable_and_default' => [
            [
                [
                    'name' => 'a',
                    'nullable' => false,
                    'default' => [],
                ],
                [
                    'name' => 'b',
                    'nullable' => true,
                    'default' => null,
                ],
            ],
            [
                [
                    'name' => 'a',
                    'nullable' => false,
                    'default' => [],
                ],
                [
                    'name' => 'b',
                    'nullable' => true,
                    'default' => null,
                ],
            ],
        ];

        yield 'sort_nullable_and_default_multiple' => [
            [
                [
                    'name' => 'a',
                    'nullable' => false,
                    'default' => [],
                ],
                [
                    'name' => 'b',
                    'nullable' => true,
                    'default' => null,
                ],
                [
                    'name' => 'c',
                    'nullable' => false,
                    'default' => null,
                ],
            ],
            [
                [
                    'name' => 'c',
                    'nullable' => false,
                    'default' => null,
                ],
                [
                    'name' => 'a',
                    'nullable' => false,
                    'default' => [],
                ],
                [
                    'name' => 'b',
                    'nullable' => true,
                    'default' => null,
                ],
            ],
        ];
    }
}
