<?php

use Symfony\Bundle\MakerBundle\Str;

echo "<?php\n";
?>

namespace <?= $namespace; ?>;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class WebTestCase extends BaseWebTestCase
{
    protected static function createAnonymousClient(array $options = [], array $server = [])
    {
        return static::createClient($options, $server);
    }

<?php
foreach ($roles as $role_name => $role):
?>
    protected static function create<?= $role_name ?>Client(array $options = [], array $server = [])
    {
        $client = static::createClient($options, $server);
        $container = $client->getContainer();

        // Uncomment the following lines and configure how to create a User with role <?= $role ?>


        // $user = $container->get(UserRepository::class)->findOneBy(['username' => '<?= Str::asSnakeCase($role_name) ?>@example.com']);
        // self::logInUser($client, 'main', $user);

        throw new \LogicException(
            __CLASS__."::create<?= $role_name ?>Client() is not implemented\n".
            'Open the WebTestCase class and customize it!'
        );

        return $client;
    }

<?php
endforeach;
?>
    protected static function logInUser(Client $client, $firewallName, UserInterface $user)
    {
        $container = $client->getContainer();
        $container->get('security.user_checker')->checkPreAuth($user);

        $token = new UsernamePasswordToken($user, null, $firewallName, $user->getRoles());

        $request = $container->get('request_stack')->getCurrentRequest();
        if (null !== $request) {
            $container->get('security.authentication.session_strategy')->onAuthentication($request, $token);
        }

        $container->get('security.token_storage')->setToken($token);

        $session = $container->get('session');
        $session->set('_security_'.$firewallName, serialize($token));
        $session->save();

        $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));
    }
}
