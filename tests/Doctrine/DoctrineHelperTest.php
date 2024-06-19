<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Doctrine;

use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;

class DoctrineHelperTest extends TestCase
{
    /**
     * @dataProvider getTypeConstantTests
     */
    public function testGetTypeConstant(string $columnType, ?string $expectedConstant)
    {
        $this->assertSame($expectedConstant, DoctrineHelper::getTypeConstant($columnType));
    }

    public function getTypeConstantTests(): \Generator
    {
        yield 'unknown_type' => ['foo', null];
        yield 'string' => ['string', 'Types::STRING'];
        yield 'datetimetz_immutable' => ['datetimetz_immutable', 'Types::DATETIMETZ_IMMUTABLE'];
    }

    /**
     * @dataProvider getCanColumnTypeBeInferredTests
     */
    public function testCanColumnTypeBeInferredByPropertyType(string $columnType, string $propertyType, bool $expected)
    {
        $this->assertSame($expected, DoctrineHelper::canColumnTypeBeInferredByPropertyType($columnType, $propertyType));
    }

    public function getCanColumnTypeBeInferredTests(): \Generator
    {
        yield 'non_matching' => [Types::TEXT, 'string', false];
        yield 'yes_matching' => [Types::STRING, 'string', true];
    }
}
