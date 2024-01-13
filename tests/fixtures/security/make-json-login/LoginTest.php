<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class LoginTest extends WebTestCase
{
    public function testJsonLogin(): void
    {
        $client = static::createClient();
        $client->jsonRequest('POST', '/api/login');

        self::assertResponseStatusCodeSame(401);

        $em = static::getContainer()->get('doctrine')->getManager();

        $user = (new User())
            ->setEmail('jr@rushlow.dev')
            ->setPassword('wordpass')
        ;

        $em->persist($user);
        $em->flush();

        $client->jsonRequest('POST', '/api/login', [
            'username' => 'jr@rushlow.dev',
            'password' => 'wordpass',
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('{"user":"jr@rushlow.dev"}', $client->getResponse()->getContent());
    }
}
