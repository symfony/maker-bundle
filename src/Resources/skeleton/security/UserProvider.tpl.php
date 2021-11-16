<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\<?= $uses_user_identifier ? 'UserNotFoundException' : 'UsernameNotFoundException' ?>;
<?= $use_legacy_password_upgrader_type ? '' : "use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;\n" ?>
<?= ($password_upgrader = interface_exists('Symfony\Component\Security\Core\User\PasswordUpgraderInterface')) ? "use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;\n" : '' ?>
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class <?= $class_name ?> implements UserProviderInterface<?= $password_upgrader ? ", PasswordUpgraderInterface\n" : "\n" ?>
{
    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * If you're not using these features, you do not need to implement
     * this method.
     *
     * @throws <?= $uses_user_identifier ? 'UserNotFoundException' :  'UsernameNotFoundException' ?> if the user is not found
     */
    public function <?= $uses_user_identifier ? 'loadUserByIdentifier($identifier)' : 'loadUserByUsername($username)' ?>: UserInterface
    {
        // Load a User object from your data source or throw <?= $uses_user_identifier ? 'UserNotFoundException' :  'UsernameNotFoundException' ?>.
        // The <?= $uses_user_identifier ? '$identifier' :  '$username' ?> argument may not actually be a username:
        // it is whatever value is being returned by the <?= $uses_user_identifier ? 'getUserIdentifier' :  'getUsername' ?>()
        // method in your User class.
        throw new \Exception('TODO: fill in <?= $uses_user_identifier ? 'loadUserByIdentifier()' : 'loadUserByUsername()' ?> inside '.__FILE__);
    }

<?php if ($uses_user_identifier) : ?>
    /**
     * @deprecated since Symfony 5.3, loadUserByIdentifier() is used instead
     */
    public function loadUserByUsername($username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

<?php endif ?>
    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof <?= $user_short_name ?>) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        // Return a User object after making sure its data is "fresh".
        // Or throw a UsernameNotFoundException if the user no longer exists.
        throw new \Exception('TODO: fill in refreshUser() inside '.__FILE__);
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass($class): bool
    {
        return <?= $user_short_name ?>::class === $class || is_subclass_of($class, <?= $user_short_name ?>::class);
    }
<?php if ($password_upgrader): ?>

    /**
     * Upgrades the hashed password of a user, typically for using a better hash algorithm.
     */
    public function upgradePassword(<?= $use_legacy_password_upgrader_type ? 'UserInterface' : 'PasswordAuthenticatedUserInterface'; ?> $user, string $newHashedPassword): void
    {
        // TODO: when hashed passwords are in use, this method should:
        // 1. persist the new password in the user storage
        // 2. update the $user object with $user->setPassword($newHashedPassword);
    }
<?php endif ?>
}
