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

use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;

class DoctrineHelperTest extends TestCase
{
    /**
     * @dataProvider getTypeConstantTests
     */
    #[DataProvider('getTypeConstantTests')]
    public function testGetTypeConstant(string $columnType, ?string $expectedConstant)
    {
        $this->assertSame($expectedConstant, DoctrineHelper::getTypeConstant($columnType));
    }

    public static function getTypeConstantTests(): \Generator
    {
        yield 'unknown_type' => ['foo', null];
        yield 'string' => ['string', 'Types::STRING'];
        yield 'datetimetz_immutable' => ['datetimetz_immutable', 'Types::DATETIMETZ_IMMUTABLE'];
    }

    /**
     * @dataProvider getCanColumnTypeBeInferredTests
     */
    #[DataProvider('getCanColumnTypeBeInferredTests')]
    public function testCanColumnTypeBeInferredByPropertyType(string $columnType, string $propertyType, bool $expected)
    {
        $this->assertSame($expected, DoctrineHelper::canColumnTypeBeInferredByPropertyType($columnType, $propertyType));
    }

    public static function getCanColumnTypeBeInferredTests(): \Generator
    {
        yield 'non_matching' => [Types::TEXT, 'string', false];
        yield 'yes_matching' => [Types::STRING, 'string', true];
        yield 'json_is_inferred_from_array' => [Types::JSON, 'array', true];
        yield 'jsonb_is_not_inferred_from_array' => ['jsonb', 'array', false];
    }

    /**
     * @dataProvider getIsReservedKeywordTests
     */
    #[DataProvider('getIsReservedKeywordTests')]
    public function testIsReservedKeyword(string $name, bool $expected)
    {
        $platform = new OraclePlatform();

        if (!method_exists($platform, 'getReservedKeywordsList')) {
            $this->markTestSkipped('DBAL 5 removes reserved keyword lists.');
        }

        $this->assertSame($expected, DoctrineHelper::isReservedKeyword($platform, $name));
    }

    public static function getIsReservedKeywordTests(): \Generator
    {
        yield 'reserved_keyword' => ['select', true];
        yield 'reserved_keyword_uppercase' => ['SELECT', true];
        yield 'regular_name' => ['product', false];
    }

    /**
     * @dataProvider getPropertyTypeForColumnTests
     */
    #[DataProvider('getPropertyTypeForColumnTests')]
    public function testGetPropertyTypeForColumn(string $columnType, ?string $expectedPropertyType)
    {
        $this->assertSame($expectedPropertyType, DoctrineHelper::getPropertyTypeForColumn($columnType));
    }

    public static function getPropertyTypeForColumnTests(): \Generator
    {
        yield 'unknown_type' => ['foo', null];
        yield 'string' => [Types::STRING, 'string'];
        yield 'json' => [Types::JSON, 'array'];
        yield 'jsonb' => ['jsonb', 'array'];
        yield 'datetime_immutable' => [Types::DATETIME_IMMUTABLE, '\\DateTimeImmutable'];
    }
}
