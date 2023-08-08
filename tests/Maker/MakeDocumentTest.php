<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Bundle\MakerBundle\Maker\MakeDocument;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Bundle\MakerBundle\Test\MakerTestRunner;
use Symfony\Component\Finder\Finder;

class MakeDocumentTest extends MakerTestCase
{
    protected function getMakerClass(): string
    {
        return MakeDocument::class;
    }

    private function createMakeDocumentTest(bool $withDatabase = true): MakerTestDetails
    {
        return $this->createMakerTest()
            ->preRun(function (MakerTestRunner $runner) use ($withDatabase) {
                if ($withDatabase) {
                    $runner->configureMongoDatabase();
                }
            });
    }

    public function getTestDetails(): Generator
    {
        yield 'it_creates_a_new_class_basic' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // document class name
                    'User',
                    // add not additional fields
                    '',
                ]);

                $this->runDocumentTest($runner);
            }),
        ];

        yield 'it_creates_a_new_class_and_api_resource' => [$this->createMakeDocumentTest()
            ->addExtraDependencies('api')
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // document class name
                    'User',
                    // Mark the document as an API Platform resource
                    'y',
                    // add not additional fields
                    '',
                ]);

                $this->assertFileExists($runner->getPath('src/Document/User.php'));

                $content = file_get_contents($runner->getPath('src/Document/User.php'));
                $this->assertStringContainsString('use ApiPlatform\Metadata\ApiResource;', $content);
                $this->assertStringContainsString('#[ApiResource]', $content);

                $this->runDocumentTest($runner);
            }),
        ];

        yield 'it_creates_a_new_class_with_fields' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $runner->runMaker([
                    // document class name
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

                $this->runDocumentTest($runner);
            }),
        ];

        yield 'it_updates_existing_document' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocument($runner, 'User-basic.php');

                $runner->runMaker([
                    // document class name
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

                $this->runDocumentTest($runner, [
                    // existing field
                    'firstName' => 'Mr. Chocolate',
                    // new field
                    'lastName' => 'Cake',
                ]);
            }),
        ];

        yield 'it_updates_document_many_to_one_no_inverse' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocument($runner, 'User-basic.php');

                $runner->runMaker([
                    // document class name
                    'UserAvatarPhoto',
                    // field name
                    'user',
                    // add a relationship field
                    'relation',
                    // the target document
                    'User',
                    // relation type
                    'ReferenceOne',
                    // nullable
                    'n',
                    // do you want to generate an inverse relation? (default to yes)
                    'n',
                    // finish adding fields
                    '',
                ]);

                $this->runCustomTest($runner, 'it_updates_document_many_to_one_no_inverse.php');
            }),
        ];

        yield 'it_adds_many_to_one_self_referencing' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocument($runner, 'User-basic.php');

                $runner->runMaker([
                    // document class name
                    'User',
                    // field name
                    'guardian',
                    // add a relationship field
                    'relation',
                    // the target document
                    'User',
                    // relation type
                    'ReferenceOne',
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

        yield 'it_adds_one_to_many_simple' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocument($runner, 'UserAvatarPhoto-basic.php');

                $runner->runMaker([
                    // document class name
                    'User',
                    // field name
                    'photos',
                    // add a relationship field
                    'relation',
                    // the target document
                    'UserAvatarPhoto',
                    // relation type
                    'ReferenceMany',
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

        yield 'it_adds_one_to_one_simple' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocument($runner, 'User-basic.php');

                $runner->runMaker([
                    // document class name
                    'UserProfile',
                    // field name
                    'user',
                    // add a relationship field
                    'relation',
                    // the target document
                    'User',
                    // relation type
                    'ReferenceOneToOne',
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

        yield 'it_adds_many_to_one_to_vendor_target' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocument($runner, 'User-basic.php');
                $this->setupGroupDocumentInVendor($runner);

                $output = $runner->runMaker([
                    // document class name
                    'User',
                    // field name
                    'userGroup',
                    // add a relationship field
                    'ReferenceOne',
                    // the target document
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

                $this->assertStringContainsString('src/Document/User.php', $output);
                $this->assertStringNotContainsString('updated: vendor/', $output);

                // sanity checks on the generated code
                $finder = new Finder();
                $finder->in($runner->getPath('src/Document'))->files()->name('*.php');
                $this->assertCount(1, $finder);

                $this->assertStringNotContainsString('inversedBy', file_get_contents($runner->getPath('src/Document/User.php')));
            }),
        ];

        yield 'it_adds_one_to_one_to_vendor_target' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocument($runner, 'User-basic.php');
                $this->setupGroupDocumentInVendor($runner);

                $output = $runner->runMaker([
                    // document class name
                    'User',
                    // field name
                    'userGroup',
                    // add a relationship field
                    'ReferenceOneToOne',
                    // the target document
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

                $this->assertStringNotContainsString('inversedBy', file_get_contents($runner->getPath('src/Document/User.php')));
            }),
        ];

        yield 'it_regenerates_documents' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocumentDirectory($runner, 'regenerate');

                $runner->runMaker([
                    // namespace: use default App\Document
                    '',
                ], '--regenerate');

                $this->runCustomTest($runner, 'it_regenerates_documents.php');
            }),
        ];

        yield 'it_regenerates_embedded_documents' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocumentDirectory($runner, 'regenerate-embedded');

                $runner->runMaker([
                    // namespace: use default App\Document
                    '',
                ], '--regenerate');

                $this->runCustomTest($runner, 'it_regenerates_embedded_documents.php');
            }),
        ];

        yield 'it_regenerates_embeddable_document' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocumentDirectory($runner, 'regenerate-embeddable');

                $runner->runMaker([
                    // namespace: use default App\Document
                    '',
                ], '--regenerate');

                $this->runCustomTest($runner, 'it_regenerates_embeddable_document.php');
            }),
        ];

        yield 'it_regenerates_with_overwrite' => [$this->createMakeDocumentTest(false)
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocument($runner, 'User-invalid-method.php');

                $runner->runMaker([
                    // namespace: use default App\Document
                    '',
                ], '--regenerate --overwrite');

                $this->runCustomTest($runner, 'it_regenerates_with_overwrite.php', false);
            }),
        ];

        yield 'it_regenerates_from_xml' => [$this->createMakeDocumentTest(false)
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocumentDirectory($runner, 'regenerate-xml-classes');
                $runner->copy(
                    'make-document/regenerate-xml',
                    ''
                );

                $this->changeToXmlMapping($runner);

                $runner->runMaker([
                    // namespace: use default App\Document
                    '',
                ], '--regenerate');

                $this->runCustomTest($runner, 'it_regenerates_from_xml.php', false);
            }),
        ];

        yield 'it_display_an_error_using_with_xml' => [$this->createMakeDocumentTest(false)
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocumentDirectory($runner, 'regenerate-xml-classes');

                $runner->copy(
                    'make-document/xml-mapping',
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

        yield 'it_can_overwrite_while_adding_fields' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocument($runner, 'User-invalid-method-no-property.php');

                $runner->runMaker([
                    // document class name
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
        yield 'it_creates_class_that_matches_existing_namespace' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocument($runner, 'User-basic.php');

                $runner->runMaker([
                    // document class name
                    'User\\Category',
                    // add not additional fields
                    '',
                ]);

                $this->runCustomTest($runner, 'it_creates_class_that_matches_existing_namespace.php');
            }),
        ];

        yield 'it_adds_embed_one_embedded' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocument($runner, 'UserAvatarPhoto-embedded.php');

                $runner->runMaker([
                    // document class name
                    'User',
                    // field name
                    'photo',
                    // add a EmbedOne field
                    'EmbedOne',
                    // the target document
                    'UserAvatarPhoto',
                    // nullable
                    'n',
                    // finish adding fields
                    '',
                ]);

                $this->runCustomTest($runner, 'it_adds_embed_one_embedded.php');
            }),
        ];

        yield 'it_adds_embed_one_non_embedded' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocument($runner, 'UserAvatarPhoto-non-embedded.php');

                $output = $runner->runMaker([
                    // document class name
                    'User',
                    // field name
                    'photo',
                    // add a EmbedOne field
                    'EmbedOne',
                    // the target document
                    'UserAvatarPhoto',
                    // finish adding fields
                    '',
                ], '', true);
                $this->assertStringContainsString('is not an EmbeddedDocument', $output);
            }),
        ];

        yield 'it_adds_embed_many_embedded' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocument($runner, 'UserAvatarPhoto-embedded.php');

                $runner->runMaker([
                    // document class name
                    'User',
                    // field name
                    'photos',
                    // add a EmbedMany field
                    'EmbedMany',
                    // the target document
                    'UserAvatarPhoto',
                    // nullable
                    'n',
                    // finish adding fields
                    '',
                ]);

                $this->runCustomTest($runner, 'it_adds_embed_many_embedded.php');
            }),
        ];

        yield 'it_adds_embed_many_non_embedded' => [$this->createMakeDocumentTest()
            ->run(function (MakerTestRunner $runner) {
                $this->copyDocument($runner, 'UserAvatarPhoto-non-embedded.php');

                $output = $runner->runMaker([
                    // document class name
                    'User',
                    // field name
                    'photos',
                    // add a EmbedMany field
                    'EmbedMany',
                    // the target document
                    'UserAvatarPhoto',
                    // finish adding fields
                    '',
                ], '', true);
                $this->assertStringContainsString('is not an EmbeddedDocument', $output);
            }),
        ];
    }

    private function runDocumentTest(MakerTestRunner $runner, array $data = []): void
    {
        $runner->renderTemplateFile(
            'make-document/GeneratedDocumentTest.php.twig',
            'tests/GeneratedDocumentTest.php',
            [
                'data' => $data,
            ]
        );

        $runner->runTests();
    }

    private function runCustomTest(MakerTestRunner $runner, string $filename, bool $withDatabase = true): void
    {
        $runner->copy(
            'make-document/tests/'.$filename,
            'tests/GeneratedDocumentTest.php'
        );

        $runner->runTests();
    }

    private function setupGroupDocumentInVendor(MakerTestRunner $runner): void
    {
        $runner->copy(
            'make-document/Group-vendor.php',
            'vendor/some-vendor/src/Group.php'
        );

        $runner->addToAutoloader(
            'Some\\Vendor\\',
            'vendor/some-vendor/src'
        );
    }

    private function changeToXmlMapping(MakerTestRunner $runner): void
    {
        $runner->modifyYamlFile('config/packages/doctrine_mongodb.yaml', function (array $data) {
            $data['doctrine_mongodb']['document_managers']['default']['mappings']['App']['type'] = 'xml';

            return $data;
        });

        $runner->replaceInFile(
            'config/packages/doctrine_mongodb.yaml',
            "dir: '%kernel.project_dir%/src/Document'",
            "dir: '%kernel.project_dir%/config/doctrine_mongodb'"
        );
    }

    private function copyDocument(MakerTestRunner $runner, string $filename): void
    {
        $documentClassName = substr(
            $filename,
            0,
            strpos($filename, '-')
        );

        $runner->copy(
            sprintf('make-document/documents/attributes/%s', $filename),
            sprintf('src/Document/%s.php', $documentClassName)
        );
    }

    private function copyDocumentDirectory(MakerTestRunner $runner, string $directory): void
    {
        $runner->copy(
            sprintf('make-document/%s/attributes', $directory),
            ''
        );
    }
}
