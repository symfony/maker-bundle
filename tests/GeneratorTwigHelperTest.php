<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\GeneratorTwigHelper;

class GeneratorTwigHelperTest extends TestCase
{
    /**
     * @dataProvider getEntityFieldPrintCodeTests
     */
    public function testGetEntityFieldPrintCode(string $entity, string $fieldName, string $fieldType, string $expect)
    {
        $generator = new GeneratorTwigHelper($this->createMock(FileManager::class));
        $field = [];
        $field['fieldName'] = $fieldName;
        $field['type'] = $fieldType;

        $result = $generator->getEntityFieldPrintCode($entity, $field);

        $this->assertSame($expect, $result);
    }

    public function getEntityFieldPrintCodeTests()
    {
        yield 'normal' => [
            'entity',
            'normal',
            'string',
            'entity.normal',
        ];

        yield 'normal_upper' => [
            'entity',
            'normalUpper',
            'string',
            'entity.normalUpper',
        ];

        yield 'underscore' => [
            'entity',
            'with_underscore',
            'string',
            'entity.withUnderscore',
        ];

        yield 'underscore_number' => [
            'entity',
            'field_100',
            'string',
            'entity.field100',
        ];

        yield 'underscore_first' => [
            'entity',
            '_field',
            'string',
            'entity.field',
        ];

        yield 'normal_datetime' => [
            'entity',
            'normal',
            'datetime',
            'entity.normal ? entity.normal|date(\'Y-m-d H:i:s\') : \'\'',
        ];

        yield 'normal_date' => [
            'entity',
            'normal',
            'date',
            'entity.normal ? entity.normal|date(\'Y-m-d\') : \'\'',
        ];

        yield 'normal_time' => [
            'entity',
            'normal',
            'time',
            'entity.normal ? entity.normal|date(\'H:i:s\') : \'\'',
        ];

        yield 'normal_array' => [
            'entity',
            'normal',
            'array',
            'entity.normal ? entity.normal|join(\', \') : \'\'',
        ];

        yield 'normal_boolean' => [
            'entity',
            'normal',
            'boolean',
            'entity.normal ? \'Yes\' : \'No\'',
        ];
    }
}
