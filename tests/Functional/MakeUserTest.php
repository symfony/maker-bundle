<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Functional;

use Symfony\Bundle\MakerBundle\Maker\MakeUser;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\WindowsSmoke;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[MakerTest(maker: MakeUser::class, profile: Profiles::MEGA)]
final class MakeUserTest extends MakerTestCase
{
    #[LegacyCase('MakeUserTest::it_generates_entity_with_password')]
    #[WindowsSmoke]
    public function testItGeneratesEntityWithPassword()
    {
        $this->app->copyFixture('standard_setup', '');

        $this->app->runMaker()
            ->answer('name of the security user class', 'User')
            ->answer('store user data in the database', 'y')
            ->answer('property name that will be the unique', 'email')
            ->answer('need to hash/check user passwords', 'y')
            ->run()
            ->assertCreated('src/Entity/User.php', 'src/Repository/UserRepository.php')
            ->assertUpdated('config/packages/security.yaml');

        $this->runUserTest('it_generates_entity_with_password.php');
    }

    #[LegacyCase('MakeUserTest::it_generates_entity_with_password_and_uuid')]
    public function testItGeneratesEntityWithPasswordAndUuid()
    {
        $this->app->copyFixture('standard_setup', '');

        $this->app->runMaker()
            ->arguments('--with-uuid')
            ->answer('name of the security user class', 'User')
            ->answer('store user data in the database', 'y')
            ->answer('property name that will be the unique', 'email')
            ->answer('need to hash/check user passwords', 'y')
            ->run()
            ->assertCreated('src/Entity/User.php', 'src/Repository/UserRepository.php');

        $this->app->assertFileContains('src/Entity/User.php', 'use Symfony\Component\Uid\Uuid;');

        $this->runUserTest('it_generates_entity_with_password_and_uuid.php');
    }

    #[LegacyCase('MakeUserTest::it_generates_entity_with_password_and_ulid')]
    public function testItGeneratesEntityWithPasswordAndUlid()
    {
        $this->app->copyFixture('standard_setup', '');

        $this->app->runMaker()
            ->arguments('--with-ulid')
            ->answer('name of the security user class', 'User')
            ->answer('store user data in the database', 'y')
            ->answer('property name that will be the unique', 'email')
            ->answer('need to hash/check user passwords', 'y')
            ->run()
            ->assertCreated('src/Entity/User.php', 'src/Repository/UserRepository.php');

        $this->app->assertFileContains('src/Entity/User.php', 'use Symfony\Component\Uid\Ulid;');

        $this->runUserTest('it_generates_entity_with_password_and_ulid.php');
    }

    #[LegacyCase('MakeUserTest::it_generates_non_entity_no_password')]
    public function testItGeneratesNonEntityNoPassword()
    {
        $this->app->copyFixture('standard_setup', '');

        $this->app->runMaker()
            ->answer('name of the security user class', 'FunUser')
            ->answer('store user data in the database', 'n')
            ->answer('property name that will be the unique', 'username')
            ->answer('need to hash/check user passwords', 'n')
            ->run()
            ->assertCreated('src/Security/FunUser.php', 'src/Security/UserProvider.php');

        // simplification: allows us to assume loadUserByIdentifier in the test
        $this->app->replaceInFile(
            'src/Security/UserProvider.php',
            'throw new \Exception(\'TODO: fill in refreshUser() inside \'.__FILE__);',
            'return $user;',
        );
        $this->app->replaceInFile(
            'src/Security/UserProvider.php',
            'throw new \Exception(\'TODO: fill in loadUserByIdentifier() inside \'.__FILE__);',
            'return (new FunUser())->setUsername($identifier);',
        );

        $this->configureLoginTestServices();

        $this->app->runGeneratedTests('it_generates_non_entity_no_password.php', covers: [
            'src/Security/FunUser.php',
            'src/Security/UserProvider.php',
        ]);
    }

    private function runUserTest(string $fixtureTestFile): void
    {
        $this->configureLoginTestServices();
        $this->app->prepareDatabase();

        $this->app->runGeneratedTests($fixtureTestFile, covers: [
            'src/Entity/User.php',
            'src/Repository/UserRepository.php',
        ]);
    }

    private function configureLoginTestServices(): void
    {
        $security = $this->app->readYaml('config/packages/security.yaml');
        $security['security']['firewalls']['main']['custom_authenticator'] = 'App\Security\AutomaticAuthenticator';
        $this->app->writeYaml('config/packages/security.yaml', $security);

        // make a service accessible in the test
        // (the real one is removed as it's never used in the app)
        $services = $this->app->readYaml('config/services.yaml');
        $services['services']['test_password_hasher'] = [
            'public' => true,
            'alias' => UserPasswordHasherInterface::class,
        ];
        $this->app->writeYaml('config/services.yaml', $services);
    }
}
