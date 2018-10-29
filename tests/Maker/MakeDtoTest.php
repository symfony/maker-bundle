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
		yield 'dto' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
                'Task',
                'Task',
				// generate helpers
				'yes',
				// omit getters
				'yes',
            ])
            ->addExtraDependencies('orm')
            ->addExtraDependencies('validator')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDto')
            ->assert(function (string $output, string $directory) {
                $this->assertContains('created: src/Form/Data/TaskData.php', $output);
				$this->assertContains('updated: src/Form/Data/TaskData.php', $output);
				$this->assertContains('\\App\\Form\\Data\\TaskData', $output);
            })
		];

        yield 'dto_getters_setters' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
                'Task',
				'Task',
				// generate helpers
				'yes',
				// omit getters
				'no',
            ])
            ->addExtraDependencies('orm')
            ->addExtraDependencies('validator')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDtoGettersSetters')
            ->assert(function (string $output, string $directory) {
                $this->assertContains('created: src/Form/Data/TaskData.php', $output);
                $this->assertContains('updated: src/Form/Data/TaskData.php', $output);
            })
		];

        yield 'dto_without_helpers' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
                'Task',
				'Task',
				// generate helpers
				'no',
				// omit getters
				'no',
            ])
            ->addExtraDependencies('orm')
            ->addExtraDependencies('validator')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDtoWithoutHelpers')
            ->assert(function (string $output, string $directory) {
                $this->assertContains('created: src/Form/Data/TaskData.php', $output);
                $this->assertContains('updated: src/Form/Data/TaskData.php', $output);
            })
		];

        yield 'dto_invalid_entity' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeDto::class),
            [
				'Task',
				// bound class, can not use "Task" because invalid entity is not in autocomplete
                '\\App\\Entity\\Task',
				// generate helpers
				'yes',
				// omit getters
				'yes',
            ])
            ->addExtraDependencies('orm')
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeDtoInvalidEntity')
            ->assert(function (string $output, string $directory) {
                $this->assertContains('The bound class is not a valid doctrine entity.', $output);
            })
        ];
    }
}
