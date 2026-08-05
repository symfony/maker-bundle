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

use Symfony\Bundle\MakerBundle\Maker\MakeEntity;
use Symfony\Bundle\MakerBundle\Tests\Harness\Application\Profiles;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\SkipIfEnv;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\SkipOnPostgres;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\UsesProfile;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\WindowsSmoke;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

/**
 * Entity creation, fields, enums and the api/turbo/custom-namespace extras.
 * Relations live in MakeEntityRelationsTest, --regenerate in
 * MakeEntityRegenerateTest.
 */
#[MakerTest(maker: MakeEntity::class, profile: Profiles::MEGA)]
final class MakeEntityTest extends MakerTestCase
{
    #[LegacyCase('MakeEntityTest::it_creates_a_new_class_basic')]
    #[WindowsSmoke]
    public function testItCreatesANewClassBasic()
    {
        $this->app->prepareDatabase();

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->acceptDefault('New property name')
            ->run()
            ->assertCreated('src/Entity/User.php', 'src/Repository/UserRepository.php');

        $this->runEntityTemplateTest();
    }

    #[LegacyCase('MakeEntityTest::it_creates_a_final_class_when_configured')]
    public function testItCreatesAFinalClassWhenConfigured()
    {
        $this->app->writeYaml('config/packages/dev/maker.yaml', ['maker' => ['generate_final_entities' => true]]);

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->acceptDefault('New property name')
            ->run();

        $this->app->assertFileContains('src/Entity/User.php', 'final class User');

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $user = new \App\Entity\User();
            self::assertNull($user->getId());
            self::assertInstanceOf(\App\Repository\UserRepository::class, static::getContainer()->get(\App\Repository\UserRepository::class));
            PHP,
            covers: ['src/Entity/User.php', 'src/Repository/UserRepository.php'],
        );
    }

    #[LegacyCase('MakeEntityTest::it_only_shows_supported_types')]
    public function testItOnlyShowsSupportedTypes()
    {
        $result = $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'Developer')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->answer('New property name', 'keyboards')
            ->answer('Field type', '?')
            ->acceptDefault('Field type')
            ->acceptDefault('Field length')
            ->acceptDefault('nullable')
            ->acceptDefault('Add another property')
            ->run();

        $result->assertOutputContains('Main Types');
        $result->assertOutputContains('* string or ascii_string');
        $result->assertOutputContains('* ManyToOne');

        $installed = require $this->app->getPath('vendor/composer/installed.php');
        if (!str_starts_with($installed['versions']['doctrine/dbal']['version'], '3.')) {
            self::assertStringNotContainsString('* object', $result->output());
        } else {
            self::assertStringContainsString('* object', $result->output());
        }

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $developer = new \App\Entity\Developer();
            $developer->setKeyboards('hhkb');
            self::assertSame('hhkb', $developer->getKeyboards());
            self::assertInstanceOf(\App\Repository\DeveloperRepository::class, static::getContainer()->get(\App\Repository\DeveloperRepository::class));
            PHP,
            covers: ['src/Entity/Developer.php', 'src/Repository/DeveloperRepository.php'],
        );
    }

    #[LegacyCase('MakeEntityTest::it_does_not_validate_entity_name_with_accent')]
    public function testItDoesNotValidateEntityNameWithAccent()
    {
        $this->app->prepareDatabase();

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'Usé')
            ->answer('Continue anyway', 'n')
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->acceptDefault('New property name')
            ->run();

        $this->runEntityTemplateTest();
    }

    #[LegacyCase('MakeEntityTest::it_creates_a_new_class_with_uuid')]
    public function testItCreatesANewClassWithUuid()
    {
        $this->app->prepareDatabase();

        $this->app->runMaker()
            ->arguments('--with-uuid')
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->acceptDefault('New property name')
            ->run();

        $this->app->assertFileContains('src/Entity/User.php', 'use Symfony\Component\Uid\Uuid;');
        $this->app->assertFileContains('src/Entity/User.php', "[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]");

        $this->runEntityTemplateTest();
    }

    #[LegacyCase('MakeEntityTest::it_creates_a_new_class_with_ulid')]
    public function testItCreatesANewClassWithUlid()
    {
        $this->app->prepareDatabase();

        $this->app->runMaker()
            ->arguments('--with-ulid')
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->acceptDefault('New property name')
            ->run();

        $this->app->assertFileContains('src/Entity/User.php', 'use Symfony\Component\Uid\Ulid;');
        $this->app->assertFileContains('src/Entity/User.php', "[ORM\CustomIdGenerator(class: 'doctrine.ulid_generator')]");

        $this->runEntityTemplateTest();
    }

    #[LegacyCase('MakeEntityTest::it_creates_a_new_class_with_fields')]
    public function testItCreatesANewClassWithFields()
    {
        $this->app->prepareDatabase();

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->answer('New property name', 'name')
            ->answer('Field type', 'string')
            ->answer('Field length', '255')
            ->answer('nullable', 'y')
            ->answer('Add another property', 'createdAt')
            ->acceptDefault('Field type')
            ->answer('nullable', 'y')
            ->acceptDefault('Add another property')
            ->run();

        $this->runEntityTemplateTest();
    }

    #[LegacyCase('MakeEntityTest::it_updates_existing_entity')]
    #[SkipOnPostgres('the fixture entity maps "user", a reserved table name there')]
    #[WindowsSmoke]
    public function testItUpdatesExistingEntity()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('User-basic.php');

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('New property name', 'lastName')
            ->answer('Field type', 'string')
            ->acceptDefault('Field length')
            ->answer('nullable', 'y')
            ->acceptDefault('Add another property')
            ->run()
            ->assertUpdated('src/Entity/User.php');

        $this->runEntityTemplateTest(
            data: ['firstName' => 'Mr. Chocolate', 'lastName' => 'Cake'],
            covers: ['src/Entity/User.php'],
        );
    }

    // see #192
    #[LegacyCase('MakeEntityTest::it_creates_class_that_matches_existing_namespace')]
    public function testItCreatesClassThatMatchesExistingNamespace()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('User-basic.php');

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User\\Category')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->acceptDefault('New property name')
            ->run();

        $this->app->updateSchema();
        $this->app->runGeneratedTests('it_creates_class_that_matches_existing_namespace.php', covers: [
            'src/Entity/User/Category.php',
            'src/Repository/User/CategoryRepository.php',
        ]);
    }

    #[LegacyCase('MakeEntityTest::it_creates_a_new_class_with_enum_field')]
    public function testItCreatesANewClassWithEnumField()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('Enum/Role-basic.php', 'src/Entity/Enum/Role.php');

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->answer('New property name', 'role')
            ->answer('Field type', 'enum')
            ->answer('Enum class', 'App\\Entity\\Enum\\Role')
            ->acceptDefault('multiple enum values')
            ->answer('nullable', 'y')
            ->acceptDefault('Add another property')
            ->run();

        $this->runEntityTemplateTest();
    }

    #[LegacyCase('MakeEntityTest::it_cannot_create_a_new_class_with_fake_enum_field')]
    public function testItCannotCreateANewClassWithFakeEnumField()
    {
        // no answers after the invalid enum class: the validator re-asks, the
        // driver closes stdin and the console aborts — like a real Ctrl-D
        $result = $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->answer('New property name', 'fakeEnum')
            ->answer('Field type', 'enum')
            ->answer('Enum class', 'App\\Enum\\Fake')
            ->allowFailure()
            ->run();

        self::assertStringContainsString('Class "App\Enum\Fake" doesn\'t exist', $result->output());
    }

    #[LegacyCase('MakeEntityTest::it_creates_a_new_class_with_enum_field_multiple_and_nullable')]
    public function testItCreatesANewClassWithEnumFieldMultipleAndNullable()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('Enum/Role-basic.php', 'src/Entity/Enum/Role.php');

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->answer('New property name', 'role')
            ->answer('Field type', 'enum')
            ->answer('Enum class', 'App\\Entity\\Enum\\Role')
            ->answer('multiple enum values', 'y')
            ->answer('nullable', 'y')
            ->acceptDefault('Add another property')
            ->run();

        $this->runEntityTemplateTest();
    }

    #[LegacyCase('MakeEntityTest::it_creates_a_new_class_and_api_resource')]
    #[UsesProfile(Profiles::GUARD)]
    public function testItCreatesANewClassAndApiResource()
    {
        $this->app->prepareDatabase();

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('Mark this class as an API Platform resource', 'y')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->acceptDefault('New property name')
            ->run()
            ->assertCreated('src/Entity/User.php', 'src/Repository/UserRepository.php');

        $this->app->assertFileContains('src/Entity/User.php', 'use ApiPlatform\Metadata\ApiResource;');
        $this->app->assertFileContains('src/Entity/User.php', '#[ApiResource]');

        $this->runEntityTemplateTest();
    }

    #[LegacyCase('MakeEntityTest::it_makes_new_entity_no_to_all_extras')]
    public function testItMakesNewEntityNoToAllExtras()
    {
        $this->app->prepareDatabase();

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->acceptDefault('New property name')
            ->run()
            ->assertCreated('src/Entity/User.php', 'src/Repository/UserRepository.php');

        $this->runEntityTemplateTest();
    }

    #[LegacyCase('MakeEntityTest::it_generates_entity_with_turbo_without_mercure')]
    #[UsesProfile(Profiles::GUARD)]
    public function testItGeneratesEntityWithTurboWithoutMercure()
    {
        $this->app->prepareDatabase();

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->acceptDefault('New property name')
            ->run()
            ->assertCreated('src/Entity/User.php', 'src/Repository/UserRepository.php');

        // lean guard: this only means anything because mercure is really absent
        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            self::assertTrue(\Composer\InstalledVersions::isInstalled('symfony/ux-turbo'));
            self::assertFalse(\Composer\InstalledVersions::isInstalled('symfony/mercure-bundle'));
            PHP);

        $this->runEntityTemplateTest();
    }

    #[LegacyCase('MakeEntityTest::it_makes_new_entity_with_mercure_broadcast')]
    #[SkipIfEnv('MAKER_SKIP_MERCURE_TEST')]
    public function testItMakesNewEntityWithMercureBroadcast()
    {
        // point the recipe's hub URL at the local hub (the docker service in CI)
        $this->app->replaceInFile(
            '.env',
            'https://example.com/.well-known/mercure',
            'http://127.0.0.1:1337/.well-known/mercure',
        );

        $this->app->prepareDatabase();

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'y')
            ->acceptDefault('New property name')
            ->run()
            ->assertCreated(
                'src/Entity/User.php',
                'src/Repository/UserRepository.php',
                'templates/broadcast/User.stream.html.twig',
            );

        $this->app->assertFileContains('src/Entity/User.php', 'use Symfony\UX\Turbo\Attribute\Broadcast;');
        $this->app->assertFileContains('src/Entity/User.php', '#[Broadcast]');

        // persisting the entity broadcasts to the hub, rendering the stream template
        $this->runEntityTemplateTest(covers: [
            'src/Entity/User.php',
            'src/Repository/UserRepository.php',
            'templates/broadcast/User.stream.html.twig',
        ]);
    }

    #[LegacyCase('MakeEntityTest::it_adds_many_to_many_with_custom_root_namespace')]
    #[UsesProfile(Profiles::MEGA_CUSTOM_NAMESPACE)]
    #[SkipOnPostgres('the fixture entity maps "user", a reserved table name there')]
    public function testItAddsManyToManyWithCustomRootNamespace()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('User-custom-namespace.php', 'src/Entity/User.php');

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'Course')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->answer('New property name', 'students')
            ->answer('Field type', 'relation')
            ->answer('What class should this entity be related to', 'User')
            ->answer('Relation type', 'ManyToMany')
            ->answer('Do you want to add a new property to', 'y')
            ->acceptDefault('New field name inside User')
            ->acceptDefault('Add another property')
            ->run()
            ->assertCreated('src/Entity/Course.php', 'src/Repository/CourseRepository.php')
            ->assertUpdated('src/Entity/User.php');

        $this->app->updateSchema();
        $this->app->runGeneratedTests('it_adds_many_to_many_with_custom_root_namespace.php', covers: [
            'src/Entity/Course.php',
            'src/Repository/CourseRepository.php',
            'src/Entity/User.php',
        ]);
    }

    private function copyEntityFixture(string $filename, ?string $target = null): void
    {
        $entityClassName = substr($filename, 0, strpos($filename, '-'));
        $this->app->copyFixture(
            'entities/attributes/'.$filename,
            $target ?? \sprintf('src/Entity/%s.php', $entityClassName),
        );
    }

    /**
     * @param array<string, string> $data
     * @param list<string>          $covers
     */
    private function runEntityTemplateTest(array $data = [], array $covers = ['src/Entity/User.php', 'src/Repository/UserRepository.php']): void
    {
        $this->app->renderFixtureTemplate('GeneratedEntityTest.php.twig', 'tests/GeneratedEntityTest.php', ['data' => $data]);
        $this->app->updateSchema();
        $this->app->runGeneratedTestFile('tests/GeneratedEntityTest.php', covers: $covers);
    }
}
