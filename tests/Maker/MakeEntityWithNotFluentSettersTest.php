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
                'name',
                'string',
                '255', // length
                // nullable
                'y',
                'email',
                'string',
                '255', // length
                // nullable
                'y',
                // finish adding fields
                '',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeEntityWithNotFluentSetters')
            ->configureDatabase()
            ->updateSchemaAfterCommand(),
        ];
    }
}
