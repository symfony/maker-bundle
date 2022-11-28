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

    private function createMakeEntityTest(bool $withDatabase = true): MakerTestDetails
    {
        return $this->createMakerTest()
            ->preRun(function (MakerTestRunner $runner) use ($withDatabase) {
                if ($withDatabase) {
                    $runner->configureDatabase();
                }
            });
    }

    private function createMakeEntityTestForMercure(): MakerTestDetails
    {
        return $this->createMakeEntityTest()
            ->preRun(function (MakerTestRunner $runner) {
                // installed manually later so that the compatibility check can run fist
                $runner->runProcess('composer require symfony/ux-turbo-mercure');
            });
    }

    public function getTestDetails(): \Generator
    {
        yield 'it_creates_a_new_class_basic' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // entity class name
                    'User',
                    // add not additional fields
                    '',
                ]);

                $this->runEntityTest($runner);
            }),
        ];

        yield 'it_creates_a_new_class_and_api_resource' => [$this->createMakeEntityTest()
            ->addExtraDependencies('api')
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // entity class name
                    'User',
                    // Mark the entity as an API Platform resource
                    'y',
                    // add not additional fields
                    '',
                ]);

                $this->assertFileExists($runner->getPath('src/Entity/User.php'));

                $content = file_get_contents($runner->getPath('src/Entity/User.php'));
                $this->assertStringContainsString('use ApiPlatform\Metadata\ApiResource;', $content);
                $this->assertStringContainsString('#[ApiResource]', $content);

                $this->runEntityTest($runner);
            }),
        ];

        yield 'it_creates_a_new_class_with_fields' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
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

                $this->runEntityTest($runner);
            }),
        ];

        yield 'it_updates_existing_entity' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-basic.php');

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

                $this->runEntityTest($runner, [
                    // existing field
                    'firstName' => 'Mr. Chocolate',
                    // new field
                    'lastName' => 'Cake',
                ]);
            }),
        ];

        yield 'it_updates_entity_many_to_one_no_inverse' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-basic.php');

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

                $this->runCustomTest($runner, 'it_updates_entity_many_to_one_no_inverse.php');
            }),
        ];

        yield 'it_adds_many_to_one_self_referencing' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-basic.php');

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

                $this->runCustomTest($runner, 'it_adds_many_to_one_self_referencing.php');
            }),
        ];

        yield 'it_adds_one_to_many_simple' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'UserAvatarPhoto-basic.php');

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

                $this->runCustomTest($runner, 'it_adds_one_to_many_simple.php');
            }),
        ];

        yield 'it_adds_many_to_many_simple' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-basic.php');

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

                $this->runCustomTest($runner, 'it_adds_many_to_many_simple.php');
            }),
        ];

        yield 'it_adds_many_to_many_with_custom_root_namespace' => [$this->createMakeEntityTest()
            ->changeRootNamespace('Custom')
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-custom-namespace.php');

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

                $this->runCustomTest($runner, 'it_adds_many_to_many_with_custom_root_namespace.php');
            }),
        ];

        yield 'it_adds_one_to_one_simple' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-basic.php');

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

                $this->runCustomTest($runner, 'it_adds_one_to_one_simple.php');
            }),
        ];

        yield 'it_adds_many_to_one_to_vendor_target' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-basic.php');
                $this->setupGroupEntityInVendor($runner);

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

                $this->assertStringContainsString('updated: src/Entity/User.php', $output);
                $this->assertStringNotContainsString('updated: vendor/', $output);

                // sanity checks on the generated code
                $finder = new Finder();
                $finder->in($runner->getPath('src/Entity'))->files()->name('*.php');
                $this->assertCount(1, $finder);

                $this->assertStringNotContainsString('inversedBy', file_get_contents($runner->getPath('src/Entity/User.php')));
            }),
        ];

        yield 'it_adds_many_to_many_to_vendor_target' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-basic.php');
                $this->setupGroupEntityInVendor($runner);

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

                $this->assertStringNotContainsString('updated: vendor/', $output);

                $this->assertStringNotContainsString('inversedBy', file_get_contents($runner->getPath('src/Entity/User.php')));
            }),
        ];

        yield 'it_adds_one_to_one_to_vendor_target' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-basic.php');
                $this->setupGroupEntityInVendor($runner);

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

                $this->assertStringNotContainsString('updated: vendor/', $output);

                $this->assertStringNotContainsString('inversedBy', file_get_contents($runner->getPath('src/Entity/User.php')));
            }),
        ];

        yield 'it_regenerates_entities' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntityDirectory($runner, 'regenerate');

                $runner->runMaker([
                    // namespace: use default App\Entity
                    '',
                ], '--regenerate');

                $this->runCustomTest($runner, 'it_regenerates_entities.php');
            }),
        ];

        yield 'it_regenerates_embedded_entities' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntityDirectory($runner, 'regenerate-embedded');

                $runner->runMaker([
                    // namespace: use default App\Entity
                    '',
                ], '--regenerate');

                $this->runCustomTest($runner, 'it_regenerates_embedded_entities.php');
            }),
        ];

        yield 'it_regenerates_embeddable_entity' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntityDirectory($runner, 'regenerate-embeddable');

                $runner->runMaker([
                    // namespace: use default App\Entity
                    '',
                ], '--regenerate');

                $this->runCustomTest($runner, 'it_regenerates_embeddable_entity.php');
            }),
        ];

        yield 'it_regenerates_with_overwrite' => [$this->createMakeEntityTest(false)
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-invalid-method.php');

                $runner->runMaker([
                    // namespace: use default App\Entity
                    '',
                ], '--regenerate --overwrite');

                $this->runCustomTest($runner, 'it_regenerates_with_overwrite.php', false);
            }),
        ];

        yield 'it_regenerates_from_xml' => [$this->createMakeEntityTest(false)
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-basic.php');

                $runner->copy(
                    'make-entity/regenerate-xml',
                    ''
                );

                $this->changeToXmlMapping($runner);

                $runner->runMaker([
                    // namespace: use default App\Entity
                    '',
                ], '--regenerate');

                $this->runCustomTest($runner, 'it_regenerates_from_xml.php', false);
            }),
        ];

        yield 'it_display_an_error_using_with_xml' => [$this->createMakeEntityTest(false)
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-basic.php');

                $runner->copy(
                    'make-entity/xml-mapping',
                    ''
                );

                $this->changeToXmlMapping($runner);

                $output = $runner->runMaker([
                    'User',
                    '',
                ], '', true /* allow failure */);

                $this->assertStringContainsString('Only attribute mapping is supported', $output);
            }),
        ];

        yield 'it_display_an_error_using_with_xml_with_new_class' => [$this->createMakeEntityTest(false)
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-basic.php');

                $runner->copy(
                    'make-entity/xml-mapping',
                    ''
                );

                $this->changeToXmlMapping($runner);

                $output = $runner->runMaker([
                    'UserAvatarPhoto',
                    '',
                ], '', true /* allow failure */);

                $this->assertStringContainsString('Only attribute mapping is supported', $output);
            }),
        ];

        yield 'it_can_overwrite_while_adding_fields' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-invalid-method-no-property.php');

                $runner->runMaker([
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
                ], '--overwrite');

                $this->runCustomTest($runner, 'it_regenerates_with_overwrite.php');
            }),
        ];

        // see #192
        yield 'it_creates_class_that_matches_existing_namespace' => [$this->createMakeEntityTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyEntity($runner, 'User-basic.php');

                $runner->runMaker([
                    // entity class name
                    'User\\Category',
                    // add not additional fields
                    '',
                ]);

                $this->runCustomTest($runner, 'it_creates_class_that_matches_existing_namespace.php');
            }),
        ];

        yield 'it_makes_new_entity_with_mercure_broadcast' => [$this->createMakeEntityTestForMercure()
            // special setup done in createMakeEntityTestForMercure()
            ->run(function (MakerTestRunner $runner) {
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

                $this->assertFileExists($runner->getPath('src/Entity/User.php'));

                $content = file_get_contents($runner->getPath('src/Entity/User.php'));
                $this->assertStringContainsString('use Symfony\UX\Turbo\Attribute\Broadcast;', $content);
                $this->assertStringContainsString('#[Broadcast]', $content);

                $skipMercureTest = $_SERVER['MAKER_SKIP_MERCURE_TEST'] ?? false;
                if (!$skipMercureTest) {
                    $this->runEntityTest($runner);
                }
            }),
        ];

        yield 'it_makes_new_entity_no_to_all_extras' => [$this->createMakeEntityTestForMercure()
            ->addExtraDependencies('api')
            // special setup done in createMakeEntityTestForMercure()
            ->run(function (MakerTestRunner $runner) {
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

                $this->assertFileExists($runner->getPath('src/Entity/User.php'));
                $this->runEntityTest($runner);
            }),
        ];
    }

    private function runEntityTest(MakerTestRunner $runner, array $data = []): void
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

    private function runCustomTest(MakerTestRunner $runner, string $filename, bool $withDatabase = true): void
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

    private function setupGroupEntityInVendor(MakerTestRunner $runner): void
    {
        $runner->copy(
            'make-entity/Group-vendor.php',
            'vendor/some-vendor/src/Group.php'
        );

        $runner->addToAutoloader(
            'Some\\\Vendor\\\\',
            'vendor/some-vendor/src'
        );
    }

    private function changeToXmlMapping(MakerTestRunner $runner): void
    {
        $runner->modifyYamlFile('config/packages/doctrine.yaml', function (array $data) {
            $data['doctrine']['orm']['mappings']['App']['type'] = 'xml';

            return $data;
        });

        $runner->replaceInFile(
            'config/packages/doctrine.yaml',
            "dir: '%kernel.project_dir%/src/Entity'",
            "dir: '%kernel.project_dir%/config/doctrine'"
        );
    }

    private function copyEntity(MakerTestRunner $runner, string $filename): void
    {
        $entityClassName = substr(
            $filename,
            0,
            strpos($filename, '-')
        );

        $runner->copy(
            sprintf('make-entity/entities/attributes/%s', $filename),
            sprintf('src/Entity/%s.php', $entityClassName)
        );
    }

    private function copyEntityDirectory(MakerTestRunner $runner, string $directory): void
    {
        $runner->copy(
            sprintf('make-entity/%s/attributes', $directory),
            ''
        );
    }
}
