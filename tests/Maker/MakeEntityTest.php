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

        yield 'its_can_create_classes_with_native_class_name' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // entity class name
                    'Directory',
                    // add not additional fields
                    '',
                ]);

                $this->runEntityTest($runner);
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
