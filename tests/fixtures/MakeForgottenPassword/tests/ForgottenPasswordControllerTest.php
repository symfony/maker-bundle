<?php

namespace App\Tests;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use App\Entity\PasswordResetToken;

class ForgottenPasswordControllerTest extends WebTestCase
{
    public function testRequestSuccessful()
    {
        self::bootKernel();

        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $em->createQuery('DELETE FROM App\\Entity\\PasswordResetToken t')
            ->execute();
        $em->createQuery('DELETE FROM App\\Entity\\User u')
            ->execute();

        $user = new User();
        $user->setEmail('foo@example.com');
        $user->setPassword('randompassword');

        $em->persist($user);
        $em->flush();

        $client = static::createClient();

        $spoolDir = $client->getContainer()->getParameter('swiftmailer.spool.default.file.path');
        $filesystem = new Filesystem();
        $filesystem->remove($spoolDir);

        // Start of our test: we request a password reset e-mail
        $crawler = $client->request('GET', '/forgotten-password/request');
        $form = $crawler->selectButton('Send e-mail')->form();
        $form['password_request_form[email]'] = 'foo@example.com';
        $client->submit($form);
        $this->assertSame(302, $client->getResponse()->getStatusCode());

        // We test the e-mail is sent, looking at the spool
        $finder = new Finder();
        $this->assertEquals(1, $finder->in($spoolDir)->files()->count());
        foreach ($finder as $file) {
            $message = unserialize($file->getContents());
            $this->assertInstanceOf(\Swift_Message::class, $message);
            $this->assertEquals('Your password reset request', $message->getSubject());
        }

        // We continue the browsing...
        $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains('An email has been sent.', $client->getResponse()->getContent());
    }

    public function testRequestNotFound()
    {
        self::bootKernel();
        $client = static::createClient();

        $spoolDir = $client->getContainer()->getParameter('swiftmailer.spool.default.file.path');
        $filesystem = new Filesystem();
        $filesystem->remove($spoolDir);

        // Start of our test: we request a password reset e-mail
        $crawler = $client->request('GET', '/forgotten-password/request');
        $form = $crawler->selectButton('Send e-mail')->form();
        $form['password_request_form[email]'] = 'anotheremail@example.com';
        $client->submit($form);
        $this->assertSame(302, $client->getResponse()->getStatusCode());

        // No emails should be sent
        $finder = new Finder();
        $this->assertEquals(0, $finder->in($spoolDir)->files()->count());

        // We continue the browsing...
        $client->followRedirect();
        // We should get the same message, the application should not disclose the user does not exist.
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains('An email has been sent.', $client->getResponse()->getContent());
    }

    public function testRequestRetryLimit()
    {
        self::bootKernel();

        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $em->createQuery('DELETE FROM App\\Entity\\PasswordResetToken t')
            ->execute();
        $em->createQuery('DELETE FROM App\\Entity\\User u')
            ->execute();

        $user = new User();
        $user->setEmail('bar@example.com');
        $user->setPassword('randompassword');

        $em->persist($user);
        $em->flush();

        $client = static::createClient();

        $spoolDir = $client->getContainer()->getParameter('swiftmailer.spool.default.file.path');
        $filesystem = new Filesystem();
        $filesystem->remove($spoolDir);

        // Start of our test: we request a password reset e-mail
        $crawler = $client->request('GET', '/forgotten-password/request');
        $form = $crawler->selectButton('Send e-mail')->form();
        $form['password_request_form[email]'] = 'bar@example.com';
        $client->submit($form);
        $this->assertSame(302, $client->getResponse()->getStatusCode());

        // We test the e-mail is sent, looking at the spool
        $finder = new Finder();
        $this->assertEquals(1, $finder->in($spoolDir)->files()->count());
        foreach ($finder as $file) {
            $message = unserialize($file->getContents());
            $this->assertInstanceOf(\Swift_Message::class, $message);
            $this->assertEquals('Your password reset request', $message->getSubject());
        }

        // We try to request again
        $crawler = $client->request('GET', '/forgotten-password/request');
        $form = $crawler->selectButton('Send e-mail')->form();
        $form['password_request_form[email]'] = 'bar@example.com';
        $client->submit($form);
        $this->assertSame(302, $client->getResponse()->getStatusCode());

        // No second email should be sent, as bar@example.com still had a valid token
        $this->assertEquals(1, $finder->count());
    }

    public function testCheckEmailNotAccessibleDirectly()
    {
        self::bootKernel();
        $client = static::createClient();

        $client->request('GET', '/forgotten-password/check-email');

        // We are redirected to the request page
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        // We continue the browsing...
        $client->followRedirect();

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testResetSuccessful()
    {
        self::bootKernel();

        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $em->createQuery('DELETE FROM App\\Entity\\PasswordResetToken t')
            ->execute();
        $em->createQuery('DELETE FROM App\\Entity\\User u')
            ->execute();

        $user = new User();
        $user->setEmail('foo@example.com');
        $user->setPassword('randompassword');

        $token = new PasswordResetToken($user);

        $em->persist($user);
        $em->persist($token);
        $em->flush();

        $client = static::createClient();

        // Start of our test: we go to the reset password form
        $client->request('GET', '/forgotten-password/reset/'.$token->getAsString());
        // We are redirected to the same page
        $this->assertSame(302, $client->getResponse()->getStatusCode());

        // We continue the browsing...
        $crawler = $client->followRedirect();
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // We fill in a new password
        $form = $crawler->selectButton('Reset password')->form();
        $form['password_resetting_form[plainPassword][first]'] = 'newpassword';
        $form['password_resetting_form[plainPassword][second]'] = 'newpassword';
        $client->submit($form);

        // It is saved, we are redirected to login
        $this->assertSame(302, $client->getResponse()->getStatusCode());

        // Token was removed from DB
        $this->assertCount(0, $em->getRepository(PasswordResetToken::class)->findBy([
            'user' => $user,
        ]));
    }


    public function testResetWrongSelector()
    {
        self::bootKernel();
        $client = static::createClient();

        $client->request('GET', '/forgotten-password/reset/randomselectorandtoken');

        // We are redirected to the same page
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        // We continue the browsing...
        $client->followRedirect();

        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testResetWrongToken()
    {
        self::bootKernel();

        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $em->createQuery('DELETE FROM App\\Entity\\PasswordResetToken t')
            ->execute();
        $em->createQuery('DELETE FROM App\\Entity\\User u')
            ->execute();

        $user = new User();
        $user->setEmail('foo@example.com');
        $user->setPassword('randompassword');

        $token = new PasswordResetToken($user);

        $em->persist($user);
        $em->persist($token);
        $em->flush();

        $client = static::createClient();

        // Start of our test: we go to the reset password form
        $client->request('GET', '/forgotten-password/reset/'.$token->getAsString().'wrong');

        // We are redirected to the same page
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        // We continue the browsing...
        $client->followRedirect();

        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // Token was removed from DB
        $this->assertCount(0, $em->getRepository(PasswordResetToken::class)->findBy([
            'user' => $user,
        ]));
    }

    public function testResetExpired()
    {
        self::bootKernel();

        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $em->createQuery('DELETE FROM App\\Entity\\PasswordResetToken t')
            ->execute();
        $em->createQuery('DELETE FROM App\\Entity\\User u')
            ->execute();

        $user = new User();
        $user->setEmail('foo@example.com');
        $user->setPassword('randompassword');

        $token = new PasswordResetToken($user);
        // We change time in token
        $reflection = new \ReflectionProperty(PasswordResetToken::class, 'requestedAt');
        $reflection->setAccessible(true);
        $reflection->setValue($token, new \DateTimeImmutable('-2 days'));

        $em->persist($user);
        $em->persist($token);
        $em->flush();

        $client = static::createClient();

        // Start of our test: we go to the reset password form
        $client->request('GET', '/forgotten-password/reset/'.$token->getAsString());

        // We are redirected to the same page
        $this->assertSame(302, $client->getResponse()->getStatusCode());
        // We continue the browsing...
        $client->followRedirect();

        $this->assertSame(404, $client->getResponse()->getStatusCode());

        // Token was removed from DB
        $this->assertCount(0, $em->getRepository(PasswordResetToken::class)->findBy([
            'user' => $user,
        ]));
    }
}
