<?php

namespace Symfony\Bundle\MakerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

class ValidatorTest extends TestCase
{
    public function testValidateLength()
    {
        $this->assertSame(100, Validator::validateLength('100'));
        $this->assertSame(99, Validator::validateLength(99));
    }

    public function testInvalidLength()
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('Invalid length "-100".');

        Validator::validateLength(-100);
    }

    public function testValidatePrecision()
    {
        $this->assertSame(15, Validator::validatePrecision('15'));
        $this->assertSame(21, Validator::validatePrecision(21));
    }

    public function testInvalidPrecision()
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('Invalid precision "66".');

        Validator::validatePrecision(66);
    }

    public function testValidateScale()
    {
        $this->assertSame(2, Validator::validateScale('2'));
        $this->assertSame(5, Validator::validateScale(5));
    }

    public function testInvalidScale()
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('Invalid scale "31".');

        Validator::validateScale(31);
    }
}