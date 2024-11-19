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
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Validator;

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

    public function testValidateClassName()
    {
        $this->assertSame('\App\Service\Foo', Validator::validateClassName('\App\Service\Foo'));
        $this->assertSame('Foo', Validator::validateClassName('Foo'));
    }

    public function testValidateEmailAddress()
    {
        $this->assertSame('jr@rushlow.dev', Validator::validateEmailAddress('jr@rushlow.dev'));
    }

    public function testInvalidateEmailAddress()
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('"badEmail" is not a valid email address.');
        Validator::validateEmailAddress('badEmail');

        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('"" is not a valid email address.');
        Validator::validateEmailAddress('');
    }

    public function testInvalidClassName()
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('"Class" is a reserved keyword and thus cannot be used as class name in PHP.');
        Validator::validateClassName('App\Entity\Class');
    }

    public function testInvalidEncodingInClassName()
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage(\sprintf('"%sController" is not a UTF-8-encoded string.', \chr(0xA6)));
        Validator::validateClassName(mb_convert_encoding('ŚController', 'ISO-8859-2', 'UTF-8'));
    }

    public function testRegisteredEntitiesExists()
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('There are no registered entities; please create an entity before using this command.');
        Validator::entityExists('App\Entity\Class', []);
    }

    public function testEntityExists()
    {
        $className = self::class;
        $this->assertSame($className, Validator::entityExists($className, [$className]));
        $this->assertSame('\\'.$className, Validator::entityExists('\\'.$className, [$className]));
    }

    public function testEntityDoesNotExist()
    {
        $className = '\\'.self::class;
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage(\sprintf('Entity "%s" doesn\'t exist; please enter an existing one or create a new one.', $className));
        Validator::entityExists($className, ['Full\Entity\DummyEntity']);
    }
}
