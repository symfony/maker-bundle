<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Support;

use PHPUnit\Framework\Assert;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\GeneratedApp;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Shared multi-step app preparations used across test classes. The legacy
 * suite copy-pasted makeUser() four times with diverging signatures; this is
 * the ONE place such recipes live.
 */
final class AppRecipes
{
    /**
     * Runs make:user as setup for makers that need an existing security user
     * (authenticators, registration, reset-password, CRUD behind a firewall).
     */
    public static function makeUser(
        GeneratedApp $app,
        string $identifier = 'email',
        string $userClass = 'User',
        bool $isEntity = true,
        bool $withPassword = true,
    ): void {
        $app->runConsole('make:user', [
            'name of the security user class' => $userClass,
            'store user data in the database' => $isEntity ? 'y' : 'n',
            'property name that will be the unique' => $identifier,
            'need to hash/check user passwords' => $withPassword ? 'y' : 'n',
        ]);
    }

    /**
     * Null mailer transport for cases that trigger emails. message_bus is
     * disabled because the profiles ship symfony/messenger: with a bus, one
     * sent email yields TWO mailer events (queued + sent), which breaks the
     * maker-generated tests' getMailerMessages() count assertions.
     */
    public static function useNullMailer(GeneratedApp $app): void
    {
        $app->writeYaml('config/packages/mailer.yaml', [
            'framework' => ['mailer' => ['dsn' => 'null://null', 'message_bus' => false]],
        ]);
    }

    /**
     * Switches the test-env password hasher to plaintext so login-flow tests
     * can persist users with literal passwords. Fails loudly if the security
     * recipe ever changes shape: a silent no-op would only surface as an
     * unexplained auth failure deep inside the nested run.
     */
    public static function useTestPlaintextHasher(GeneratedApp $app): void
    {
        $security = $app->readYaml('config/packages/security.yaml');
        Assert::assertTrue(
            isset($security['security']['firewalls']) && isset($security['when@test']['security']['password_hashers']),
            'config/packages/security.yaml no longer matches the recipe shape this recipe patches.',
        );

        $security['when@test']['security']['password_hashers'] = [PasswordAuthenticatedUserInterface::class => 'plaintext'];
        $app->writeYaml('config/packages/security.yaml', $security);
    }
}
