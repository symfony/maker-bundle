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
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\DirtiesVendor;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\LegacyCase;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\MakerTest;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\SkipOnPostgres;
use Symfony\Bundle\MakerBundle\Tests\Harness\Attribute\WindowsSmoke;
use Symfony\Bundle\MakerBundle\Tests\Harness\MakerTestCase;

#[MakerTest(maker: MakeEntity::class, profile: Profiles::MEGA)]
final class MakeEntityRelationsTest extends MakerTestCase
{
    #[LegacyCase('MakeEntityTest::it_updates_entity_many_to_one_no_inverse')]
    #[SkipOnPostgres('make:entity rejects the property name "user", a reserved word there')]
    #[WindowsSmoke]
    public function testItUpdatesEntityManyToOneNoInverse()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('User-basic.php');

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'UserAvatarPhoto')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->answer('New property name', 'user')
            ->answer('Field type', 'relation')
            ->answer('What class should this entity be related to', 'User')
            ->answer('Relation type', 'ManyToOne')
            ->answer('allowed to be null', 'n')
            ->answer('Do you want to add a new property to', 'n')
            ->acceptDefault('Add another property')
            ->run();

        $this->runCustomTest('it_updates_entity_many_to_one_no_inverse.php', covers: [
            'src/Entity/UserAvatarPhoto.php',
            'src/Repository/UserAvatarPhotoRepository.php',
        ]);
    }

    #[LegacyCase('MakeEntityTest::it_adds_many_to_one_self_referencing')]
    #[SkipOnPostgres('the fixture entity maps "user", a reserved table name there')]
    public function testItAddsManyToOneSelfReferencing()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('User-basic.php');

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('New property name', 'guardian')
            ->answer('Field type', 'relation')
            ->answer('What class should this entity be related to', 'User')
            ->answer('Relation type', 'ManyToOne')
            ->answer('allowed to be null', 'y')
            ->acceptDefault('Do you want to add a new property to')
            ->answer('New field name inside', 'dependants')
            ->acceptDefault('Add another property')
            ->run()
            ->assertUpdated('src/Entity/User.php');

        $this->runCustomTest('it_adds_many_to_one_self_referencing.php', covers: ['src/Entity/User.php']);
    }

    #[LegacyCase('MakeEntityTest::it_adds_one_to_many_simple')]
    #[SkipOnPostgres('make:entity rejects the default inverse field name "user", a reserved word there')]
    public function testItAddsOneToManySimple()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('UserAvatarPhoto-basic.php');

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->answer('New property name', 'photos')
            ->answer('Field type', 'relation')
            ->answer('What class should this entity be related to', 'UserAvatarPhoto')
            ->answer('Relation type', 'OneToMany')
            ->acceptDefault('New field name inside')
            ->answer('allowed to be null', 'n')
            ->answer('orphanRemoval', 'y')
            ->acceptDefault('Add another property')
            ->run();

        $this->runCustomTest('it_adds_one_to_many_simple.php', covers: [
            'src/Entity/User.php',
            'src/Entity/UserAvatarPhoto.php',
            'src/Repository/UserRepository.php',
        ]);
    }

    #[LegacyCase('MakeEntityTest::it_adds_many_to_many_simple')]
    #[SkipOnPostgres('the fixture entity maps "user", a reserved table name there')]
    public function testItAddsManyToManySimple()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('User-basic.php');

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'Course')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->answer('New property name', 'students')
            ->answer('Field type', 'relation')
            ->answer('What class should this entity be related to', 'User')
            ->answer('Relation type', 'ManyToMany')
            ->answer('Do you want to add a new property to', 'y')
            ->acceptDefault('New field name inside')
            ->acceptDefault('Add another property')
            ->run();

        $this->runCustomTest('it_adds_many_to_many_simple.php', covers: [
            'src/Entity/Course.php',
            'src/Entity/User.php',
            'src/Repository/CourseRepository.php',
        ]);
    }

    #[LegacyCase('MakeEntityTest::it_adds_one_to_one_simple')]
    #[SkipOnPostgres('make:entity rejects the property name "user", a reserved word there')]
    public function testItAddsOneToOneSimple()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('User-basic.php');

        $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'UserProfile')
            ->answer('Mark this class as an API Platform resource', 'n')
            ->answer('broadcast entity updates using Symfony UX Turbo', 'n')
            ->answer('New property name', 'user')
            ->answer('Field type', 'relation')
            ->answer('What class should this entity be related to', 'User')
            ->answer('Relation type', 'OneToOne')
            ->answer('allowed to be null', 'n')
            ->answer('Do you want to add a new property to', 'y')
            ->acceptDefault('New field name inside')
            ->acceptDefault('Add another property')
            ->run();

        $this->runCustomTest('it_adds_one_to_one_simple.php', covers: [
            'src/Entity/User.php',
            'src/Entity/UserProfile.php',
            'src/Repository/UserProfileRepository.php',
        ]);
    }

    #[LegacyCase('MakeEntityTest::it_adds_many_to_one_to_vendor_target')]
    #[DirtiesVendor]
    public function testItAddsManyToOneToVendorTarget()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('User-basic.php');
        $this->setupGroupEntityInVendor();

        $result = $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('New property name', 'userGroup')
            ->answer('Field type', 'ManyToOne')
            ->answer('What class should this entity be related to', 'Some\\Vendor\\Group')
            ->acceptDefault('allowed to be null')
            ->acceptDefault('Add another property')
            ->run();

        $result->assertOutputContains('src/Entity/User.php');
        self::assertStringNotContainsString('updated: vendor/', $result->output());
        $this->app->assertFileNotContains('src/Entity/User.php', 'inversedBy');

        $this->assertVendorRelationExecutes('setUserGroup', 'getUserGroup');
    }

    #[LegacyCase('MakeEntityTest::it_adds_many_to_many_to_vendor_target')]
    #[DirtiesVendor]
    public function testItAddsManyToManyToVendorTarget()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('User-basic.php');
        $this->setupGroupEntityInVendor();

        $result = $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('New property name', 'userGroups')
            ->answer('Field type', 'ManyToMany')
            ->answer('What class should this entity be related to', 'Some\\Vendor\\Group')
            ->acceptDefault('Add another property')
            ->run();

        self::assertStringNotContainsString('updated: vendor/', $result->output());
        $this->app->assertFileNotContains('src/Entity/User.php', 'inversedBy');

        $this->app->assertGeneratedCodeRuns(<<<'PHP'
            $user = new \App\Entity\User();
            $group = new \Some\Vendor\Group();

            $user->addUserGroup($group);
            self::assertCount(1, $user->getUserGroups());
            PHP,
            covers: ['src/Entity/User.php'],
        );
    }

    #[LegacyCase('MakeEntityTest::it_adds_one_to_one_to_vendor_target')]
    #[DirtiesVendor]
    public function testItAddsOneToOneToVendorTarget()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('User-basic.php');
        $this->setupGroupEntityInVendor();

        $result = $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('New property name', 'userGroup')
            ->answer('Field type', 'OneToOne')
            ->answer('What class should this entity be related to', 'Some\\Vendor\\Group')
            ->acceptDefault('allowed to be null')
            ->acceptDefault('Add another property')
            ->run();

        self::assertStringNotContainsString('updated: vendor/', $result->output());
        $this->app->assertFileNotContains('src/Entity/User.php', 'inversedBy');

        $this->assertVendorRelationExecutes('setUserGroup', 'getUserGroup');
    }

    #[LegacyCase('MakeEntityTest::it_adds_many_to_many_between_same_entity_name_different_namespace')]
    #[SkipOnPostgres('the fixture entities map "user", a reserved table name there')]
    public function testItAddsManyToManyBetweenSameEntityNameDifferentNamespace()
    {
        $this->app->prepareDatabase();
        $this->copyEntityFixture('User-basic.php');
        $this->copyEntityFixture('Friend/User-sub-namespace.php');

        $result = $this->app->runMaker()
            ->answer('Class name of the entity to create or update', 'User')
            ->answer('New property name', 'friends')
            ->answer('Field type', 'relation')
            ->answer('What class should this entity be related to', 'Friend\\User')
            ->answer('Relation type', 'ManyToMany')
            ->answer('Do you want to add a new property to', 'y')
            ->acceptDefault('New field name inside')
            ->acceptDefault('Add another property')
            ->run();

        $result->assertUpdated('src/Entity/User.php', 'src/Entity/Friend/User.php');

        // the wizard must spell out both ambiguous "User" classes when
        // describing the relation types
        $result->assertOutputContains('ManyToOne    Each User relates to (has) one Friend\User.');
        $result->assertOutputContains('Each Friend\User can relate to (can have) many User objects.');
        $result->assertOutputContains('OneToMany    Each User can relate to (can have) many Friend\User objects.');
        $result->assertOutputContains('Each Friend\User relates to (has) one User.');
        $result->assertOutputContains('ManyToMany   Each User can relate to (can have) many Friend\User objects.');
        $result->assertOutputContains('Each Friend\User can also relate to (can also have) many User objects.');
        $result->assertOutputContains('OneToOne     Each User relates to (has) exactly one Friend\User.');
        $result->assertOutputContains('Each Friend\User also relates to (has) exactly one User.');

        // second same-short-name pitfall (finding #8): Doctrine derives BOTH
        // join columns as "user_id" and silently collapses them into one:
        // the user must disambiguate the join table, exactly as done here
        $this->app->replaceInFile(
            'src/Entity/User.php',
            "#[ORM\ManyToMany(targetEntity: \App\Entity\Friend\User::class, inversedBy: 'users')]",
            <<<'EOT'
                #[ORM\ManyToMany(targetEntity: \App\Entity\Friend\User::class, inversedBy: 'users')]
                    #[ORM\JoinTable(name: 'user_friends')]
                    #[ORM\JoinColumn(name: 'user_source_id')]
                    #[ORM\InverseJoinColumn(name: 'friend_user_id')]
                EOT,
        );

        // the generated cross-namespace code could never boot before the
        // ClassSourceManipulator FQCN-on-conflict fix; this executes it
        $this->runCustomTest('it_adds_many_to_many_between_same_entity_name_different_namespace.php', covers: [
            'src/Entity/User.php',
            'src/Entity/Friend/User.php',
        ]);
    }

    private function assertVendorRelationExecutes(string $setter, string $getter): void
    {
        $this->app->assertGeneratedCodeRuns(<<<PHP
            \$user = new \\App\\Entity\\User();
            \$group = new \\Some\\Vendor\\Group();

            \$user->{$setter}(\$group);
            self::assertSame(\$group, \$user->{$getter}());
            PHP,
            covers: ['src/Entity/User.php'],
        );
    }

    private function setupGroupEntityInVendor(): void
    {
        $this->app->copyFixture('Group-vendor.php', 'vendor/some-vendor/src/Group.php');
        $this->app->addToAutoloader('Some\\Vendor\\', 'vendor/some-vendor/src');
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
     * @param list<string> $covers
     */
    private function runCustomTest(string $fixtureTestFile, array $covers): void
    {
        $this->app->updateSchema();
        $this->app->runGeneratedTests($fixtureTestFile, covers: $covers);
    }
}
