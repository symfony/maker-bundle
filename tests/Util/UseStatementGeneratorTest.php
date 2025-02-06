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
use Symfony\Bundle\MakerBundle\Util\Sorter;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class UseStatementGeneratorTest extends TestCase
{
    public function testUseStatements(): void
    {
        $unsorted = new UseStatementGenerator([
            Sorter::class,
            \App\Controller\SomeController::class,
            \SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelper::class,
            \Symfony\Bundle\MakerBundle\Test\MakerTestCase::class,
        ]);

        $expected = <<< 'EOT'
            use App\Controller\SomeController;
            use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
            use Symfony\Bundle\MakerBundle\Util\Sorter;
            use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelper;

            EOT;
        self::assertSame($expected, (string) $unsorted);
    }

    public function testComplexStatements(): void
    {
        $unsorted = new UseStatementGenerator([
            \Symfony\Bundle\FrameworkBundle\Controller\AbstractController::class,
            \App\Form\RegistrationFormType::class,
            \App\Entity\User::class,
            \Symfony\Bridge\Twig\Mime\TemplatedEmail::class,
            \Symfony\Component\HttpFoundation\Request::class,
            \Symfony\Component\HttpFoundation\Response::class,
            \Symfony\Component\Routing\Attribute\Route::class,
            \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface::class,
            \App\Security\EmailVerifier::class,
            \Symfony\Component\Mime\Address::class,
            \SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface::class,
            \Doctrine\ORM\EntityManagerInterface::class,
        ]);

        $expected = <<< 'EOT'
            use App\Entity\User;
            use App\Form\RegistrationFormType;
            use App\Security\EmailVerifier;
            use Doctrine\ORM\EntityManagerInterface;
            use Symfony\Bridge\Twig\Mime\TemplatedEmail;
            use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
            use Symfony\Component\HttpFoundation\Request;
            use Symfony\Component\HttpFoundation\Response;
            use Symfony\Component\Mime\Address;
            use Symfony\Component\Routing\Attribute\Route;
            use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
            use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

            EOT;
        self::assertSame($expected, (string) $unsorted);
    }

    public function testUseStatementsWithAliases(): void
    {
        $unsorted = new UseStatementGenerator([
            \Symfony\UX\Turbo\Attribute\Broadcast::class,
            \ApiPlatform\Core\Annotation\ApiResource::class,
            [\Doctrine\ORM\Mapping::class => 'ORM'],
        ]);

        $expected = <<< 'EOT'
            use ApiPlatform\Core\Annotation\ApiResource;
            use Doctrine\ORM\Mapping as ORM;
            use Symfony\UX\Turbo\Attribute\Broadcast;

            EOT;
        self::assertSame($expected, (string) $unsorted);
    }

    public function testUseStatementsWithDuplicates(): void
    {
        $unsorted = new UseStatementGenerator([
            \Symfony\UX\Turbo\Attribute\Broadcast::class,
            \ApiPlatform\Core\Annotation\ApiResource::class,
            \ApiPlatform\Core\Annotation\ApiResource::class,
        ]);

        $expected = <<< 'EOT'
            use ApiPlatform\Core\Annotation\ApiResource;
            use Symfony\UX\Turbo\Attribute\Broadcast;

            EOT;
        self::assertSame($expected, (string) $unsorted);
    }

    public function testUseStatementShortName()
    {
        $statement = new UseStatementGenerator([
            \Symfony\UX\Turbo\Attribute\Broadcast::class,
            \ApiPlatform\Core\Annotation\ApiResource::class,
            [\Doctrine\ORM\Mapping::class => 'ORM'],
        ]);

        self::assertSame('Broadcast', $statement->getShortName(\Symfony\UX\Turbo\Attribute\Broadcast::class));
        self::assertSame('ApiResource', $statement->getShortName(\ApiPlatform\Core\Annotation\ApiResource::class));
        self::assertSame('ORM', $statement->getShortName(\Doctrine\ORM\Mapping::class));
        self::assertSame('ORM\\Entity', $statement->getShortName(\Doctrine\ORM\Mapping\Entity::class));
    }

    public function testHasUseStatement()
    {
        $statement = new UseStatementGenerator([
            \Symfony\UX\Turbo\Attribute\Broadcast::class,
            \ApiPlatform\Core\Annotation\ApiResource::class,
            [\Doctrine\ORM\Mapping::class => 'ORM'],
        ]);

        self::assertTrue($statement->hasUseStatement(\ApiPlatform\Core\Annotation\ApiResource::class));
        self::assertTrue($statement->hasUseStatement(\Symfony\UX\Turbo\Attribute\Broadcast::class));
        self::assertTrue($statement->hasUseStatement(\Doctrine\ORM\Mapping::class));
        self::assertFalse($statement->hasUseStatement(\Doctrine\ORM\Cache::class));
    }
}
