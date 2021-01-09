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
use Symfony\Component\Finder\Finder;

class MakeEntityTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'entity_new' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'User',
                // add not additional fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'entity_new_api_resource' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'User',
                // Mark the entity as an API Platform resource
                'y',
                // add not additional fields
                '',
            ])
            ->addExtraDependencies('api')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand()
            ->assert(function (string $output, string $directory) {
                $this->assertFileExists($directory.'/src/Entity/User.php');

                $content = file_get_contents($directory.'/src/Entity/User.php');
                $this->assertStringContainsString('use ApiPlatform\Core\Annotation\ApiResource;', $content);
                $this->assertStringContainsString('@ApiResource', $content);
            }),
        ];

        yield 'entity_with_fields' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
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
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'entity_updating_main' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
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
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityUpdate')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'entity_many_to_one_simple_with_inverse' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
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
                '',
                // field name on opposite side - use default 'userAvatarPhotos'
                '',
                // orphanRemoval (default to no)
                '',
                // finish adding fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityManyToOne')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'entity_many_to_one_simple_no_inverse' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
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
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityManyToOneNoInverse')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'entity_many_to_one_self_referencing' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
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
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntitySelfReferencing')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'entity_exists_in_root' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'Directory',
                // field name
                'parentDirectory',
                // add a relationship field
                'relation',
                // the target entity
                'Directory',
                // relation type
                'ManyToOne',
                // nullable
                'y',
                // do you want to generate an inverse relation? (default to yes)
                '',
                // field name on opposite side
                'childDirectories',
                // orphanRemoval (default to no)
                '',
                // finish adding fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityExistsInRoot')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'entity_one_to_many_simple' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
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
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityOneToMany')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'entity_many_to_many_simple' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
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
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityManyToMany')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'entity_many_to_many_simple_in_custom_root_namespace' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
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
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityManyToManyInCustomNamespace')
            ->changeRootNamespace('Custom')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'entity_one_to_one_simple' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
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
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityOneToOne')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];

        yield 'entity_many_to_one_vendor_target' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
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
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRelationVendorTarget')
            ->configureDatabase()
            ->addReplacement(
                'composer.json',
                '"App\\\Tests\\\": "tests/",',
                '"App\\\Tests\\\": "tests/",'."\n".'            "Some\\\Vendor\\\": "vendor/some-vendor/src",'
            )
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('updated: src/Entity/User.php', $output);
                $this->assertStringNotContainsString('updated: vendor/', $output);

                // sanity checks on the generated code
                $finder = new Finder();
                $finder->in($directory.'/src/Entity')->files()->name('*.php');
                $this->assertCount(1, $finder);

                $this->assertStringNotContainsString('inversedBy', file_get_contents($directory.'/src/Entity/User.php'));
            }),
        ];

        yield 'entity_many_to_many_vendor_target' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
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
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRelationVendorTarget')
            ->configureDatabase()
            ->addReplacement(
                'composer.json',
                '"App\\\Tests\\\": "tests/",',
                '"App\\\Tests\\\": "tests/",'."\n".'            "Some\\\Vendor\\\": "vendor/some-vendor/src",'
            )
            ->assert(function (string $output, string $directory) {
                $this->assertStringNotContainsString('updated: vendor/', $output);

                $this->assertStringNotContainsString('inversedBy', file_get_contents($directory.'/src/Entity/User.php'));
            }),
        ];

        yield 'entity_one_to_one_vendor_target' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
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
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRelationVendorTarget')
            ->configureDatabase()
            ->addReplacement(
                'composer.json',
                '"App\\\Tests\\\": "tests/",',
                '"App\\\Tests\\\": "tests/",'."\n".'            "Some\\\Vendor\\\": "vendor/some-vendor/src",'
            )
            ->assert(function (string $output, string $directory) {
                $this->assertStringNotContainsString('updated: vendor/', $output);

                $this->assertStringNotContainsString('inversedBy', file_get_contents($directory.'/src/Entity/User.php'));
            }),
        ];

        yield 'entity_regenerate' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // namespace: use default App\Entity
                '',
            ])
            ->setArgumentsString('--regenerate')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRegenerate')
            ->configureDatabase(true),
        ];

        yield 'entity_regenerate_embeddable_object' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // namespace: use default App\Entity
                '',
            ])
            ->setArgumentsString('--regenerate')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRegenerateEmbeddableObject')
            ->configureDatabase(),
        ];

        yield 'entity_regenerate_embeddable' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // namespace: use default App\Entity
                '',
            ])
            ->setArgumentsString('--regenerate --overwrite')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRegenerateEmbedable')
            ->configureDatabase(),
        ];

        yield 'entity_regenerate_overwrite' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // namespace: use default App\Entity
                '',
            ])
            ->setArgumentsString('--regenerate --overwrite')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRegenerateOverwrite')
            ->configureDatabase(false),
        ];

        yield 'entity_regenerate_xml' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // namespace: use default App\Entity
                '',
            ])
            ->setArgumentsString('--regenerate')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityRegenerateXml')
            ->addReplacement(
                'config/packages/doctrine.yaml',
                'type: annotation',
                'type: xml'
            )
            ->addReplacement(
                'config/packages/doctrine.yaml',
                "dir: '%kernel.project_dir%/src/Entity'",
                "dir: '%kernel.project_dir%/config/doctrine'"
            )
            ->configureDatabase(false),
        ];

        yield 'entity_xml_mapping_error_existing' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                'User',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityXmlMappingError')
            ->addReplacement(
                'config/packages/doctrine.yaml',
                'type: annotation',
                'type: xml'
            )
            ->addReplacement(
                'config/packages/doctrine.yaml',
                "dir: '%kernel.project_dir%/src/Entity'",
                "dir: '%kernel.project_dir%/config/doctrine'"
            )
            ->configureDatabase(false)
            ->setCommandAllowedToFail(true)
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('Only annotation mapping is supported', $output);
            }),
        ];

        yield 'entity_xml_mapping_error_new_class' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                'UserAvatarPhoto',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityXmlMappingError')
            ->addReplacement(
                'config/packages/doctrine.yaml',
                'type: annotation',
                'type: xml'
            )
            ->addReplacement(
                'config/packages/doctrine.yaml',
                "dir: '%kernel.project_dir%/src/Entity'",
                "dir: '%kernel.project_dir%/config/doctrine'"
            )
            ->configureDatabase(false)
            ->setCommandAllowedToFail(true)
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('Only annotation mapping is supported', $output);
            }),
        ];

        yield 'entity_updating_overwrite' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'User',
                // field name
                'firstName',
                'string',
                '', // length (default 255)
                // nullable
                '',
                // finish adding fields
                '',
            ])
            ->setArgumentsString('--overwrite')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityOverwrite'),
        ];

        // see #192
        yield 'entity_into_sub_namespace_matching_entity' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeEntity::class),
            [
                // entity class name
                'Product\\Category',
                // add not additional fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntitySubNamespaceMatchingEntity')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];
    }
}
