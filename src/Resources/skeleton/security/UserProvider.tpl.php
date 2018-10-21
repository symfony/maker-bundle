<?= "<?php" . PHP_EOL ?>

namespace <?= $namespace; ?>;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class <?= $class_name ?> implements UserProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username): UserInterface
    {
        // Load a User object from your data source or throw UsernameNotFoundException.
        // The $username argument may not actually be a username:
        // it is whatever value is being returned by the getUsername()
        // method in your User class.
        throw new \Exception('TODO: fill in loadUserByUsername() inside '.__FILE__);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof <?= $user_short_name ?>) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        /* @var <?= $user_short_name ?> $user */

        // Return a User object after making sure its data is "fresh".
        // Or throw a UsernameNotFoundException if the user no longer exists.
        throw new \Exception('TODO: fill in refreshUser() inside '.__FILE__);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class): bool
    {
        return <?= $user_short_name ?>::class === $class;
    }
}
