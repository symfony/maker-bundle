<?php

namespace App\Tests;

use App\Entity\User;
use App\Security\AppTestSecurity52LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class SecurityControllerTest extends WebTestCase
{
    public function testGeneratedAuthenticatorHasExpectedConstructorArgs(): void
    {
        $authenticatorReflection = new \ReflectionClass(AppTestSecurity52LoginFormAuthenticator::class);
        $constructorParameters = $authenticatorReflection->getConstructor()->getParameters();

        self::assertSame(UrlGeneratorInterface::class, $constructorParameters[0]->getType()->getName());
        self::assertSame('urlGenerator', $constructorParameters[0]->getName());
    }

    public function testLoginFormAuthenticatorUsingSecurity51(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/login');

        self::assertEquals(200, $client->getResponse()->getStatusCode());

        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $user = (new User())->setUserEmail('test@symfony.com')
            ->setPassword('password');
        $em->persist($user);
        $em->flush();

        $form = $crawler->filter('form')->form();
        $form->setValues(
            [
                'userEmail' => 'test@symfony.com',
                'password' => 'foo',
            ]
        );
        $crawler = $client->submit($form);

        if (500 === $client->getResponse()->getStatusCode()) {
            self::assertEquals('', $crawler->filter('h1.exception-message')->text());
        }

        self::assertEquals(302, $client->getResponse()->getStatusCode());

        $client->followRedirect();

        self::assertEquals(200, $client->getResponse()->getStatusCode());
        self::assertStringContainsString('Invalid credentials.', $client->getResponse()->getContent());

        $form->setValues(
            [
                'userEmail' => 'test@symfony.com',
                'password' => 'password',
            ]
        );
        $client->submit($form);

        self::assertStringContainsString('TODO: provide a valid redirect', $client->getResponse()->getContent());
        self::assertNotNull($token = $client->getContainer()->get('security.token_storage')->getToken());
        self::assertInstanceOf(User::class, $token->getUser());
    }
}
