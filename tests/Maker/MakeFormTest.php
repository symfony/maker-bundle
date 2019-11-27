<?php

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeForm;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeFormTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'form_basic' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeForm::class),
            [
                // form name
                'FooBar',
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeForm'),
        ];

        yield 'form_with_entity' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeForm::class),
            [
                // Entity name
                'SourFoodType',
                'SourFood',
            ])
            ->addExtraDependencies('orm')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFormForEntity'),
        ];

        yield 'form_for_non_entity_dto' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeForm::class),
            [
                // Entity name
                'TaskType',
                '\\App\\Form\\Data\\TaskData',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFormForNonEntityDto'),
        ];

        yield 'form_for_sti_entity' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeForm::class),
            [
                // Entity name
                'SourFoodType',
                'SourFood',
            ])
            ->addExtraDependencies('orm')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFormSTIEntity'),
        ];

        yield 'form_for_embebadle_entity' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeForm::class),
            [
                // Entity name
                'FoodType',
                'Food',
            ])
            ->addExtraDependencies('orm')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeFormEmbedableEntity'),
        ];
    }
}
