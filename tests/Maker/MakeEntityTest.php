<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeEntity;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class MakeEntityTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeEntity::class;
    }

    private static function createMakeEntityTest(bool $withDatabase = true): MakerTestDetails
    {
        return self::buildMakerTest()
            ->preRun(static function (MakerTestRunner $runner) use ($withDatabase) {
                if ($withDatabase) {
                    $runner->configureDatabase();
                }
            });
    }

    private static function createMakeEntityTestForMercure(): MakerTestDetails
    {
        if (getenv('MAKER_SKIP_MERCURE_TEST')) {
            // This test is skipped, don't worry about persistence
            return self::buildMakerTest()
                ->skipTest('MAKER_SKIP_MERCURE_TEST set to true')
            ;
        }

        return self::createMakeEntityTest()
            ->preRun(static function (MakerTestRunner $runner) {
                // installed manually later so that the compatibility check can run first
                $runner->runProcess('composer require symfony/ux-turbo');
            })
            ->addExtraDependencies('mercure', 'twig')
        ;
    }

    public static function getTestDetails(): \Generator
    {
        yield 'it_creates_a_new_class_basic' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // entity class name
                    'User',
                    // add not additional fields
                    '',
                ]);

                self::runEntityTest($runner);
            }),
        ];

        yield 'it_creates_a_final_class_when_configured' => [self::createMakeEntityTest(withDatabase: false)
            ->run(static function (MakerTestRunner $runner) {
                $runner->writeFile(
                    'config/packages/dev/maker.yaml',
                    Yaml::dump(['maker' => ['generate_final_entities' => true]])
                );

                $runner->runMaker([
                    // entity class name
                    'User',
                    // add no additional fields
                    '',
                ]);

                self::assertStringContainsString(
                    'final class User',
                    file_get_contents($runner->getPath('src/Entity/User.php'))
                );
            }),
        ];

        yield 'it_only_shows_supported_types' => [self::createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // entity class name
                    'Developer',
                    // property name
                    'keyboards',
                    // field type
                    '?',
                    // use default type
                    '',
                    // default length
                    '',
                    // nullable
                    '',
                    // no more properties
                    '',
                ]);

                self::assertStringContainsString('Main Types', $output);
                self::assertStringContainsString('* string or ascii_string', $output);
                self::assertStringContainsString('* ManyToOne', $output);

                // get the dependencies installed in the test project (tmp/cache/TEST)
                $installedVersions = require $runner->getPath('vendor/composer/installed.php');

                if (!str_starts_with($installedVersions['versions']['doctrine/dbal']['version'], '3.')) {
                    self::assertStringNotContainsString('* object', $output);
                } else {
                    self::assertStringContainsString('* object', $output);
                }
            }),
        ];

        yield 'it_does_not_validate_entity_name_with_accent' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // entity class with accent
                    'Usé',
                    // Say no,
                    'n',
                    // entity class without accent
                    'User',
                    // no fields
                    '',
                ]);

                self::runEntityTest($runner);
            }),
        ];

        yield 'it_creates_a_new_class_and_api_resource' => [self::createMakeEntityTest()
            ->addExtraDependencies('api')
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // entity class name
                    'User',
                    // Mark the entity as an API Platform resource
                    'y',
                    // add not additional fields
                    '',
                ]);

                self::assertFileExists($runner->getPath('src/Entity/User.php'));

                $content = file_get_contents($runner->getPath('src/Entity/User.php'));
                self::assertStringContainsString('use ApiPlatform\Metadata\ApiResource;', $content);
                self::assertStringContainsString('#[ApiResource]', $content);

                self::runEntityTest($runner);
            }),
        ];

        yield 'it_creates_a_new_class_with_uuid' => [self::createMakeEntityTest()
            ->addExtraDependencies('symfony/uid')
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // entity class name
                    'User',
                    // add not additional fields
                    '',
                ], '--with-uuid');

                self::assertFileExists($runner->getPath('src/Entity/User.php'));

                $content = file_get_contents($runner->getPath('src/Entity/User.php'));
                self::assertStringContainsString('use Symfony\Component\Uid\Uuid;', $content);
                self::assertStringContainsString('[ORM\CustomIdGenerator(class: \'doctrine.uuid_generator\')]', $content);

                self::runEntityTest($runner);
            }),
        ];

        yield 'it_creates_a_new_class_with_ulid' => [self::createMakeEntityTest()
            ->addExtraDependencies('symfony/uid')
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // entity class name
                    'User',
                    // add not additional fields
                    '',
                ], '--with-ulid');

                self::assertFileExists($runner->getPath('src/Entity/User.php'));

                $content = file_get_contents($runner->getPath('src/Entity/User.php'));
                self::assertStringContainsString('use Symfony\Component\Uid\Ulid;', $content);
                self::assertStringContainsString('[ORM\CustomIdGenerator(class: \'doctrine.ulid_generator\')]', $content);

                self::runEntityTest($runner);
            }),
        ];

        yield 'it_creates_a_new_class_with_uuid_when_name_is_passed_as_argument' => [self::createMakeEntityTest()
            ->addExtraDependencies('symfony/uid')
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // add no additional fields
                    '',
                ], 'User --with-uuid');

                self::assertFileExists($runner->getPath('src/Entity/User.php'));

                $content = file_get_contents($runner->getPath('src/Entity/User.php'));
                self::assertStringContainsString('use Symfony\Component\Uid\Uuid;', $content);
                self::assertStringContainsString('[ORM\CustomIdGenerator(class: \'doctrine.uuid_generator\')]', $content);

                self::runEntityTest($runner);
            }),
        ];

        yield 'it_creates_a_new_class_with_uuid_under_no_interaction' => [self::createMakeEntityTest()
            ->addExtraDependencies('symfony/uid')
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([], '--no-interaction User --with-uuid');

                self::assertFileExists($runner->getPath('src/Entity/User.php'));

                $content = file_get_contents($runner->getPath('src/Entity/User.php'));
                self::assertStringContainsString('use Symfony\Component\Uid\Uuid;', $content);
                self::assertStringContainsString('[ORM\CustomIdGenerator(class: \'doctrine.uuid_generator\')]', $content);

                self::runEntityTest($runner);
            }),
        ];

        yield 'it_creates_a_new_class_with_fields' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // entity class name
                    'User',
                    // add not additional fields
                    'name',
                    'string',
                    '255', // length
                    // nullable
                    'y',
                    'createdAt',
                    // use default datetime
                    '',
                    // nullable
                    'y',
                    // finish adding fields
                    '',
                ]);

                self::runEntityTest($runner);
            }),
        ];

        yield 'it_updates_existing_entity' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-basic.php');

                $runner->runMaker([
                    // entity class name
                    'User',
                    // add additional fields
                    'lastName',
                    'string',
                    '', // length (default 255)
                    // nullable
                    'y',
                    // finish adding fields
                    '',
                ]);

                self::runEntityTest($runner, [
                    // existing field
                    'firstName' => 'Mr. Chocolate',
                    // new field
                    'lastName' => 'Cake',
                ]);
            }),
        ];

        yield 'it_updates_entity_many_to_one_no_inverse' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-basic.php');

                $runner->runMaker([
                    // entity class name
                    'UserAvatarPhoto',
                    // field name
                    'user',
                    // add a relationship field
                    'relation',
                    // the target entity
                    'User',
                    // relation type
                    'ManyToOne',
                    // nullable
                    'n',
                    // do you want to generate an inverse relation? (default to yes)
                    'n',
                    // finish adding fields
                    '',
                ]);

                self::runCustomTest($runner, 'it_updates_entity_many_to_one_no_inverse.php');
            }),
        ];

        yield 'it_adds_many_to_one_self_referencing' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-basic.php');

                $runner->runMaker([
                    // entity class name
                    'User',
                    // field name
                    'guardian',
                    // add a relationship field
                    'relation',
                    // the target entity
                    'User',
                    // relation type
                    'ManyToOne',
                    // nullable
                    'y',
                    // do you want to generate an inverse relation? (default to yes)
                    '',
                    // field name on opposite side
                    'dependants',
                    // orphanRemoval (default to no)
                    '',
                    // finish adding fields
                    '',
                ]);

                self::runCustomTest($runner, 'it_adds_many_to_one_self_referencing.php');
            }),
        ];

        yield 'it_adds_one_to_many_simple' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'UserAvatarPhoto-basic.php');

                $runner->runMaker([
                    // entity class name
                    'User',
                    // field name
                    'photos',
                    // add a relationship field
                    'relation',
                    // the target entity
                    'UserAvatarPhoto',
                    // relation type
                    'OneToMany',
                    // field name on opposite side - use default 'user'
                    '',
                    // nullable
                    'n',
                    // orphanRemoval
                    'y',
                    // finish adding fields
                    '',
                ]);

                self::runCustomTest($runner, 'it_adds_one_to_many_simple.php');
            }),
        ];

        yield 'it_adds_two_relations_in_one_run' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-basic.php');
                self::copyEntity($runner, 'UserAvatarPhoto-basic.php');

                $runner->runMaker([
                    // entity class name
                    'Bar',
                    'author',
                    'relation',
                    'User',
                    'ManyToOne',
                    // nullable
                    'n',
                    // map the inverse side
                    'y',
                    // inverse property name - use the default
                    '',
                    // orphanRemoval
                    'n',
                    'photo',
                    'relation',
                    'UserAvatarPhoto',
                    'ManyToOne',
                    'n',
                    'y',
                    '',
                    'n',
                    // finish adding fields
                    '',
                ]);

                // writing the second relation used to feed the previous target's manipulator
                // back into this entity's file, leaving Bar.php with a copy of User
                self::assertStringContainsString('class Bar', file_get_contents($runner->getPath('src/Entity/Bar.php')));
                self::assertStringContainsString('class User', file_get_contents($runner->getPath('src/Entity/User.php')));
                self::assertStringContainsString('class UserAvatarPhoto', file_get_contents($runner->getPath('src/Entity/UserAvatarPhoto.php')));

                $bar = file_get_contents($runner->getPath('src/Entity/Bar.php'));
                self::assertStringContainsString('private ?User $author = null;', $bar);
                self::assertStringContainsString('private ?UserAvatarPhoto $photo = null;', $bar);
            }),
        ];

        yield 'it_adds_many_to_many_simple' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-basic.php');

                $runner->runMaker([
                    // entity class name
                    'Course',
                    // field name
                    'students',
                    // add a relationship field
                    'relation',
                    // the target entity
                    'User',
                    // relation type
                    'ManyToMany',
                    // inverse side?
                    'y',
                    // field name on opposite side - use default 'courses'
                    '',
                    // finish adding fields
                    '',
                ]);

                self::runCustomTest($runner, 'it_adds_many_to_many_simple.php');
            }),
        ];

        yield 'it_adds_many_to_many_with_custom_root_namespace' => [self::createMakeEntityTest()
            ->changeRootNamespace('Custom')
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-custom-namespace.php');

                $runner->writeFile(
                    'config/packages/dev/maker.yaml',
                    Yaml::dump(['maker' => ['root_namespace' => 'Custom']])
                );

                $runner->runMaker([
                    // entity class name
                    'Course',
                    // field name
                    'students',
                    // add a relationship field
                    'relation',
                    // the target entity
                    'User',
                    // relation type
                    'ManyToMany',
                    // inverse side?
                    'y',
                    // field name on opposite side - use default 'courses'
                    '',
                    // finish adding fields
                    '',
                ]);

                self::runCustomTest($runner, 'it_adds_many_to_many_with_custom_root_namespace.php');
            }),
        ];

        yield 'it_adds_many_to_many_between_same_entity_name_different_namespace' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-basic.php');
                self::copyEntity($runner, 'Friend/User-sub-namespace.php');

                $output = $runner->runMaker([
                    // entity class name
                    'User',
                    // field name
                    'friends',
                    // add a relationship field
                    'relation',
                    // the target entity
                    'Friend\\User',
                    // relation type
                    'ManyToMany',
                    // inverse side?
                    'y',
                    // field name on opposite side - use default 'courses'
                    '',
                    // finish adding fields
                    '',
                ]);

                self::assertStringContainsString('src/Entity/User.php', $output);
                self::assertStringContainsString('src/Entity/Friend/User.php', $output);
                self::assertStringContainsString('ManyToOne    Each User relates to (has) one Friend\User.', $output);
                self::assertStringContainsString('Each Friend\User can relate to (can have) many User objects.', $output);
                self::assertStringContainsString('OneToMany    Each User can relate to (can have) many Friend\User objects.', $output);
                self::assertStringContainsString('Each Friend\User relates to (has) one User.', $output);
                self::assertStringContainsString('ManyToMany   Each User can relate to (can have) many Friend\User objects.', $output);
                self::assertStringContainsString('Each Friend\User can also relate to (can also have) many User objects.', $output);
                self::assertStringContainsString('OneToOne     Each User relates to (has) exactly one Friend\User.', $output);
                self::assertStringContainsString('Each Friend\User also relates to (has) exactly one User.', $output);

                // self::runCustomTest($runner, 'it_adds_many_to_many_between_same_entity_name_different_namespace.php');
            }),
        ];

        yield 'it_adds_one_to_one_simple' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-basic.php');

                $runner->runMaker([
                    // entity class name
                    'UserProfile',
                    // field name
                    'user',
                    // add a relationship field
                    'relation',
                    // the target entity
                    'User',
                    // relation type
                    'OneToOne',
                    // nullable
                    'n',
                    // inverse side?
                    'y',
                    // field name on opposite side - use default 'userProfile'
                    '',
                    // finish adding fields
                    '',
                ]);

                self::runCustomTest($runner, 'it_adds_one_to_one_simple.php');
            }),
        ];

        yield 'it_adds_many_to_one_to_vendor_target' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-basic.php');
                self::setupGroupEntityInVendor($runner);

                $output = $runner->runMaker([
                    // entity class name
                    'User',
                    // field name
                    'userGroup',
                    // add a relationship field
                    'ManyToOne',
                    // the target entity
                    'Some\\Vendor\\Group',
                    // nullable
                    '',
                    /*
                     * normally, we ask for the field on the *other* side, but we
                     * do not here, since the other side won't be mapped.
                     */
                    // finish adding fields
                    '',
                ]);

                self::assertStringContainsString('src/Entity/User.php', $output);
                self::assertStringNotContainsString('updated: vendor/', $output);

                // sanity checks on the generated code
                $finder = new Finder();
                $finder->in($runner->getPath('src/Entity'))->files()->name('*.php');
                self::assertCount(1, $finder);

                self::assertStringNotContainsString('inversedBy', file_get_contents($runner->getPath('src/Entity/User.php')));
            }),
        ];

        yield 'it_adds_many_to_many_to_vendor_target' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-basic.php');
                self::setupGroupEntityInVendor($runner);

                $output = $runner->runMaker([
                    // entity class name
                    'User',
                    // field name
                    'userGroups',
                    // add a relationship field
                    'ManyToMany',
                    // the target entity
                    'Some\Vendor\Group',
                    /*
                     * normally, we ask for the field on the *other* side, but we
                     * do not here, since the other side won't be mapped.
                     */
                    // finish adding fields
                    '',
                ]);

                self::assertStringNotContainsString('updated: vendor/', $output);

                self::assertStringNotContainsString('inversedBy', file_get_contents($runner->getPath('src/Entity/User.php')));
            }),
        ];

        yield 'it_adds_one_to_one_to_vendor_target' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-basic.php');
                self::setupGroupEntityInVendor($runner);

                $output = $runner->runMaker([
                    // entity class name
                    'User',
                    // field name
                    'userGroup',
                    // add a relationship field
                    'OneToOne',
                    // the target entity
                    'Some\Vendor\Group',
                    // nullable,
                    '',
                    /*
                     * normally, we ask for the field on the *other* side, but we
                     * do not here, since the other side won't be mapped.
                     */
                    // finish adding fields
                    '',
                ]);

                self::assertStringNotContainsString('updated: vendor/', $output);

                self::assertStringNotContainsString('inversedBy', file_get_contents($runner->getPath('src/Entity/User.php')));
            }),
        ];

        yield 'it_regenerates_entities' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntityDirectory($runner, 'regenerate');

                $runner->runMaker([
                    // namespace: use default App\Entity
                    '',
                ], '--regenerate');

                self::runCustomTest($runner, 'it_regenerates_entities.php');
            }),
        ];

        yield 'it_regenerates_embedded_entities' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntityDirectory($runner, 'regenerate-embedded');

                $runner->runMaker([
                    // namespace: use default App\Entity
                    '',
                ], '--regenerate');

                self::runCustomTest($runner, 'it_regenerates_embedded_entities.php');
            }),
        ];

        yield 'it_regenerates_embeddable_entity' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntityDirectory($runner, 'regenerate-embeddable');

                $runner->runMaker([
                    // namespace: use default App\Entity
                    '',
                ], '--regenerate');

                self::runCustomTest($runner, 'it_regenerates_embeddable_entity.php');
            }),
        ];

        yield 'it_regenerates_with_overwrite' => [self::createMakeEntityTest(false)
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-invalid-method.php');

                $runner->runMaker([
                    // namespace: use default App\Entity
                    '',
                ], '--regenerate --overwrite');

                self::runCustomTest($runner, 'it_regenerates_with_overwrite.php', false);
            }),
        ];

        yield 'it_can_overwrite_while_adding_fields' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-invalid-method-no-property.php');

                $runner->runMaker([
                    // entity class name
                    'User',
                    // field name
                    'firstName',
                    'string',
                    '',
                    '', // length (default 255)
                    // nullable
                    '',
                    // finish adding fields
                    '',
                ], '--overwrite');

                self::runCustomTest($runner, 'it_regenerates_with_overwrite.php');
            }),
        ];

        // see #192
        yield 'it_creates_class_that_matches_existing_namespace' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-basic.php');

                $runner->runMaker([
                    // entity class name
                    'User\\Category',
                    // add not additional fields
                    '',
                ]);

                self::runCustomTest($runner, 'it_creates_class_that_matches_existing_namespace.php');
            }),
        ];

        yield 'it_makes_new_entity_with_mercure_broadcast' => [self::createMakeEntityTestForMercure()
            // special setup done in createMakeEntityTestForMercure()
            ->run(static function (MakerTestRunner $runner) {
                $runner->replaceInFile(
                    '.env',
                    'https://example.com/.well-known/mercure',
                    'http://127.0.0.1:1337/.well-known/mercure'
                );

                $runner->runMaker([
                    // entity class name
                    'User',
                    // Mark the entity as broadcasted
                    'y',
                    // add not additional fields
                    '',
                ]);

                self::assertFileExists($runner->getPath('src/Entity/User.php'));

                $content = file_get_contents($runner->getPath('src/Entity/User.php'));
                self::assertStringContainsString('use Symfony\UX\Turbo\Attribute\Broadcast;', $content);
                self::assertStringContainsString('#[Broadcast]', $content);

                self::runEntityTest($runner);
            }),
        ];

        yield 'it_makes_new_entity_no_to_all_extras' => [self::createMakeEntityTestForMercure()
            ->addExtraDependencies('api')
            // special setup done in createMakeEntityTestForMercure()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // entity class name
                    'User',
                    // Mark the entity as not an API Platform resource
                    'n',
                    // Mark the entity as not broadcasted
                    'n',
                    // add not additional fields
                    '',
                ]);

                self::assertFileExists($runner->getPath('src/Entity/User.php'));
                self::runEntityTest($runner);
            }),
        ];

        yield 'it_generates_entity_with_turbo_without_mercure' => [self::createMakeEntityTest()
            ->preRun(static function (MakerTestRunner $runner) {
                $runner->runProcess('composer require symfony/ux-turbo');
            })
            ->addExtraDependencies('twig')
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker([
                    'User', // entity class
                    'n', // no broadcast
                    '',
                ]);

                self::assertFileExists($runner->getPath('src/Entity/User.php'));
            }),
        ];

        yield 'it_creates_a_new_class_with_enum_field' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'Enum/Role-basic.php');

                $runner->runMaker([
                    // entity class name
                    'User',
                    // add additional field
                    'role',
                    'enum',
                    'App\\Entity\\Enum\\Role',
                    '',
                    // nullable
                    'y',
                    // finish adding fields
                    '',
                ]);

                self::runEntityTest($runner);
            }),
        ];

        yield 'it_cannot_create_a_new_class_with_fake_enum_field' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                $output = $runner->runMaker([
                    // entity class name
                    'User',
                    // add additional field
                    'fakeEnum',
                    'enum',
                    'App\\Enum\\Fake',
                    '',
                    // nullable
                    'y',
                    // finish adding fields
                    '',
                ], '', true);

                self::assertStringContainsString('Class "App\Enum\Fake" doesn\'t exist', $output);
            }),
        ];

        yield 'it_creates_a_new_class_with_enum_field_multiple_and_nullable' => [self::createMakeEntityTest()
        ->run(static function (MakerTestRunner $runner) {
            self::copyEntity($runner, 'Enum/Role-basic.php');

            $runner->runMaker([
                // entity class name
                'User',
                // add additional field
                'role',
                'enum',
                'App\\Entity\\Enum\\Role',
                // multiple
                'y',
                // nullable
                'y',
                // finish adding fields
                '',
            ]);

            self::runEntityTest($runner);
        }),
        ];

        yield 'it_creates_fields_from_the_field_option' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                $runner->runMaker(
                    [],
                    '--no-interaction User --field=name:string:100 --field=biography:text? --field=balance:decimal:10:2 --field=createdAt?'
                );

                $entity = file_get_contents($runner->getPath('src/Entity/User.php'));

                self::assertStringContainsString('#[ORM\Column(length: 100)]', $entity);
                self::assertStringContainsString('private ?string $name = null;', $entity);
                self::assertStringContainsString('#[ORM\\Column(type: Types::TEXT, nullable: true)]', $entity);
                self::assertStringContainsString('#[ORM\\Column(type: Types::DECIMAL, precision: 10, scale: 2)]', $entity);
                // the type of "createdAt" is guessed from its name, exactly as in interactive mode
                self::assertStringContainsString('private ?\\DateTimeImmutable $createdAt = null;', $entity);
                // a field that is not marked nullable leaves the attribute alone
                self::assertStringNotContainsString('nullable: false', $entity);

                self::runEntityTest($runner, ['name' => 'John', 'balance' => '10.50']);
            }),
        ];

        yield 'it_creates_enum_fields_from_the_field_option' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'Enum/Role-basic.php');

                // "role" resolves by short name, "otherRoles" by full class name; both are
                // nullable so that the generated entity test can persist a User without them
                $runner->runMaker(
                    [],
                    // double quotes, because cmd.exe does not treat single quotes as quoting
                    '--no-interaction User --field=role:enum:Role? --field="otherRoles:enum:App\\Entity\\Enum\\Role?,multiple"'
                );

                $entity = file_get_contents($runner->getPath('src/Entity/User.php'));

                self::assertStringContainsString('#[ORM\\Column(nullable: true, enumType: Role::class)]', $entity);
                self::assertStringContainsString('private ?Role $role = null;', $entity);
                self::assertStringContainsString('Types::SIMPLE_ARRAY', $entity);
                // an enum is not a string field, so it must not pick up a length
                self::assertStringNotContainsString('length: 255', $entity);

                self::runEntityTest($runner);
            }),
        ];

        yield 'it_creates_relations_from_the_relation_option' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-basic.php');

                $runner->runMaker(
                    [],
                    '--no-interaction BlogPost'
                    .' --relation=author:ManyToOne:User'
                    .' --relation=editors:ManyToMany:User,target-field=editedPosts'
                    .' --relation=comments:OneToMany:User,target-field=commentedPost'
                    .' --relation=cover:OneToOne:User?'
                    .' --relation=parent:ManyToOne:BlogPost?,target-field=children'
                );

                $blogPost = file_get_contents($runner->getPath('src/Entity/BlogPost.php'));
                $user = file_get_contents($runner->getPath('src/Entity/User.php'));

                self::assertStringContainsString('#[ORM\\ManyToOne(inversedBy: \'blogPosts\')]', $blogPost);
                // without a "?" a relation is NOT NULL, which is where this differs from the
                // interactive mode - there, nullable is the default answer
                self::assertStringContainsString('#[ORM\\JoinColumn(nullable: false)]', $blogPost);
                self::assertStringContainsString('private ?User $author = null;', $blogPost);
                self::assertStringContainsString('#[ORM\\ManyToMany(targetEntity: User::class, inversedBy: \'editedPosts\')]', $blogPost);
                self::assertStringContainsString('#[ORM\\OneToMany(targetEntity: User::class, mappedBy: \'commentedPost\')]', $blogPost);
                self::assertStringContainsString('private ?User $cover = null;', $blogPost);
                // a self reference resolves even though the class is generated afterwards
                self::assertStringContainsString('#[ORM\\ManyToOne(targetEntity: self::class, inversedBy: \'children\')]', $blogPost);
                self::assertStringContainsString('#[ORM\\OneToMany(targetEntity: self::class, mappedBy: \'parent\')]', $blogPost);

                // the property added to the other class is named after this entity by default
                self::assertStringContainsString('#[ORM\\OneToMany(targetEntity: BlogPost::class, mappedBy: \'author\')]', $user);
                self::assertStringContainsString('private Collection $blogPosts;', $user);
                self::assertStringContainsString('#[ORM\\ManyToMany(targetEntity: BlogPost::class, mappedBy: \'editors\')]', $user);
                self::assertStringContainsString('private ?BlogPost $commentedPost = null;', $user);
                // a OneToOne does not map its inverse side unless asked to
                self::assertStringNotContainsString('$cover', $user);

                // and Doctrine accepts every mapping that came out of it
                $runner->updateSchema();
            }),
        ];

        yield 'it_rejects_invalid_relation_options' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                self::copyEntity($runner, 'User-basic.php');

                $invalidRelations = [
                    '--relation=author:ManyToOne' => 'is missing the class it relates to',
                    '--relation=author:ManyToOne:Nope' => 'Unknown class "Nope"',
                    '--relation=author:Wrong:User' => 'Invalid relation type "Wrong"',
                    '--relation=tags:ManyToMany:User?' => 'cannot be nullable',
                    '--relation=author:ManyToOne:User?,orphan-removal' => 'orphan-removal',
                    '--relation=comments:OneToMany:User,target-field=none' => 'target-field=none',
                    '--relation=author:ManyToOne:User,target-field' => 'needs a value',
                    '--relation=author:ManyToOne:User,nope' => 'Unknown modifier "nope"',
                    // both would name their property on User after this entity
                    '--relation=createdBy:ManyToOne:User --relation=updatedBy:ManyToOne:User' => 'blogPosts',
                ];

                foreach ($invalidRelations as $arguments => $expectedError) {
                    $output = $runner->runMaker(
                        [],
                        \sprintf('--no-interaction BlogPost %s', $arguments),
                        allowedToFail: true
                    );

                    self::assertStringContainsString($expectedError, $output, \sprintf('"%s" was not rejected.', $arguments));
                    // nothing is written before every definition has been parsed
                    self::assertFileDoesNotExist($runner->getPath('src/Entity/BlogPost.php'));
                }
            }),
        ];

        yield 'it_rejects_invalid_field_options' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                $invalidDefinitions = [
                    // relations need follow-up questions, so they stay interactive-only
                    'author:ManyToOne' => 'cannot add the relation "author"',
                    'status:enum' => 'An enum field needs the enum class',
                    'status:enum:Nope' => 'No backed enum "Nope" was found',
                    'name:nope' => 'Invalid type "nope"',
                    'name:string,multiple' => 'Unknown modifier "multiple"',
                    'name:string,' => 'has an empty modifier',
                    // the identifier the entity is about to be generated with is already taken
                    'id:integer' => 'The "id" property already exists.',
                    'name:string:abc' => 'Invalid length "abc".',
                    'body:text:100' => 'has more options than the type',
                    // reserved words and invalid property names are a clean error, not a stack trace
                    '1name' => 'is not a valid PHP property name',
                ];

                foreach ($invalidDefinitions as $definition => $expectedError) {
                    $output = $runner->runMaker(
                        [],
                        \sprintf('--no-interaction User --field=%s', $definition),
                        allowedToFail: true
                    );

                    self::assertStringContainsString($expectedError, $output, \sprintf('Definition "%s" was not rejected.', $definition));
                    // nothing is written before every definition has been parsed
                    self::assertFileDoesNotExist($runner->getPath('src/Entity/User.php'));
                }
            }),
        ];

        yield 'it_rejects_the_field_and_relation_options_when_regenerating' => [self::createMakeEntityTest()
            ->run(static function (MakerTestRunner $runner) {
                foreach (['--field=name:string', '--relation=author:ManyToOne:User'] as $option) {
                    $output = $runner->runMaker(
                        [],
                        \sprintf('--no-interaction --regenerate %s', $option),
                        allowedToFail: true
                    );

                    self::assertStringContainsString('cannot be combined with', $output, \sprintf('"%s" was not rejected.', $option));
                }
            }),
        ];
    }

    /** @param array<string, mixed> $data */
    private static function runEntityTest(MakerTestRunner $runner, array $data = []): void
    {
        $runner->renderTemplateFile(
            'make-entity/GeneratedEntityTest.php.twig',
            'tests/GeneratedEntityTest.php',
            [
                'data' => $data,
            ]
        );

        $runner->updateSchema();
        $runner->runTests();
    }

    private static function runCustomTest(MakerTestRunner $runner, string $filename, bool $withDatabase = true): void
    {
        $runner->copy(
            'make-entity/tests/'.$filename,
            'tests/GeneratedEntityTest.php'
        );

        if ($withDatabase) {
            $runner->updateSchema();
        }
        $runner->runTests();
    }

    private static function setupGroupEntityInVendor(MakerTestRunner $runner): void
    {
        $runner->copy(
            'make-entity/Group-vendor.php',
            'vendor/some-vendor/src/Group.php'
        );

        $runner->addToAutoloader(
            'Some\\Vendor\\',
            'vendor/some-vendor/src'
        );
    }

    private static function copyEntity(MakerTestRunner $runner, string $filename): void
    {
        $entityClassName = substr(
            $filename,
            0,
            strpos($filename, '-')
        );

        $runner->copy(
            \sprintf('make-entity/entities/attributes/%s', $filename),
            \sprintf('src/Entity/%s.php', $entityClassName)
        );
    }

    private static function copyEntityDirectory(MakerTestRunner $runner, string $directory): void
    {
        $runner->copy(
            \sprintf('make-entity/%s/attributes', $directory),
            ''
        );
    }
}
