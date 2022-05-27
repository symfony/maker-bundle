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

use Symfony\Bundle\MakerBundle\Maker\MakeDto;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeDtoTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'dto_annotations' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
                1,
                // Add mutator to Entity (default)
                '',
            ])
            ->setArgumentsString('TaskData Task')
            ->addExtraDependencies('doctrine')
            ->addExtraDependencies('validator')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDtoAnnotations')
            ->assert(function (string $output) {
                $this->assertContains('created: src/Dto/TaskData.php', $output);
                $this->assertContains('updated: src/Dto/TaskData.php', $output);
                $this->assertContains('\\App\\Dto\\TaskData', $output);
            }),
        ];

        yield 'dto_composite_id' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
                // Mutable public (for simplicity)
                2,
                // Add mutator to Entity (default)
                '',
            ])
            ->setArgumentsString('TaskData Task')
            ->addExtraDependencies('doctrine')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDtoCompositeId')
            ->assert(function (string $output) {
                $this->assertContains('created: src/Dto/TaskData.php', $output);
                $this->assertContains('\\App\\Dto\\TaskData', $output);
            }),
        ];

        yield 'dto_getters_setters' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
                // Mutable, with getters & setters
                1,
                // Add mutator to Entity (default)
                '',
            ])
            ->setArgumentsString('TaskData Task')
            ->addExtraDependencies('doctrine')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDtoGettersSetters')
            ->assert(function (string $output) {
                $this->assertContains('created: src/Dto/TaskData.php', $output);
                $this->assertContains('updated: src/Dto/TaskData.php', $output);
            }),
        ];

        yield 'dto_invalid_entity' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
                // Mutable, with getters & setters
                1,
                // Add mutator to Entity (default)
                '',
            ])
            // bound class, can not use "Task" because invalid entity is not in autocomplete
            ->setArgumentsString('TaskData \\App\\Entity\\Task')
            ->addExtraDependencies('doctrine')
            ->setCommandAllowedToFail(true)
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDtoInvalidEntity')
            ->assert(function (string $output) {
                $this->assertContains('The bound class is not a valid doctrine entity.', $output);
            }),
        ];

        yield 'dto_immutable' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
                // Immutable, with getters only
                3,
                // Add mutator to Entity (default)
                '',
            ])
            ->setArgumentsString('TaskData Task')
            ->addExtraDependencies('doctrine')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDtoImmutable')
            ->assert(function (string $output) {
                $this->assertContains('created: src/Dto/TaskData.php', $output);
                $this->assertContains('updated: src/Dto/TaskData.php', $output);
                $this->assertContains('\\App\\Dto\\TaskData', $output);
            }),
        ];

        yield 'dto_mapped_super_class' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
                // Mutable public (for simplicity)
                2,
                // Add mutator to Entity (default)
                '',
            ])
            ->setArgumentsString('TaskData Task')
            ->addExtraDependencies('doctrine')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDtoMappedSuperClass')
            ->assert(function (string $output) {
                $this->assertContains('created: src/Dto/TaskData.php', $output);
                $this->assertContains('updated: src/Dto/TaskData.php', $output);
                $this->assertContains('\\App\\Dto\\TaskData', $output);
            }),
        ];

        yield 'dto_missing_getters_setters' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
                // Mutable, with getters & setters
                1,
                // Add mutator to Entity
                'yes',
            ])
            ->setArgumentsString('TaskData Task')
            ->addExtraDependencies('doctrine')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDtoMissingGettersSetters')
            ->assert(function (string $output) {
                $this->assertContains('created: src/Dto/TaskData.php', $output);
                $this->assertContains('updated: src/Dto/TaskData.php', $output);
                $this->assertContains('\\App\\Dto\\TaskData', $output);
                $this->assertContains('The maker found missing getters/setters for properties in the entity.', $output);
            }),
        ];

        yield 'dto_mutator' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
                // Mutable public (for simplicity)
                2,
                // Add mutator to Entity
                'yes',
            ])
            ->setArgumentsString('TaskData Task')
            ->addExtraDependencies('doctrine')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDtoMutator')
            ->assert(function (string $output) {
                $this->assertContains('created: src/Dto/TaskData.php', $output);
                $this->assertContains('updated: src/Dto/TaskData.php', $output);
            }),
        ];

        yield 'dto_no_mutator' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
                // Mutable public (for simplicity)
                2,
                // Add no mutator to Entity
                'no',
            ])
            ->setArgumentsString('TaskData Task')
            ->addExtraDependencies('doctrine')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDtoNoMutator')
            ->assert(function (string $output) {
                $this->assertContains('created: src/Dto/TaskData.php', $output);
                $this->assertContains('updated: src/Dto/TaskData.php', $output);
            }),
        ];

        yield 'dto_public' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
                // Mutable public
                2,
                // Add mutator to Entity (default)
                '',
            ])
            ->setArgumentsString('TaskData Task')
            ->addExtraDependencies('doctrine')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDtoPublic')
            ->assert(function (string $output) {
                $this->assertContains('created: src/Dto/TaskData.php', $output);
                $this->assertContains('updated: src/Dto/TaskData.php', $output);
            }),
        ];

        yield 'dto_validator_yaml_xml' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
                // Mutable public (for simplicity)
                2,
                // Add mutator to Entity (default)
                '',
            ])
            ->setArgumentsString('TaskData Task')
            ->addExtraDependencies('doctrine')
            ->addExtraDependencies('validator')
            ->addExtraDependencies('yaml')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDtoValidatorYamlXml')
            ->assert(function (string $output) {
                $this->assertContains('created: src/Dto/TaskData.php', $output);
                $this->assertContains('updated: src/Dto/TaskData.php', $output);
                $this->assertContains('The entity possibly uses Yaml/Xml validators.', $output);
            }),
        ];
    }
}
