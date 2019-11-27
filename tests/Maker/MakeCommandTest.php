<?php

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeCommand;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeCommandTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'command' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCommand::class),
            [
                // command name
                'app:foo',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCommand'),
        ];

        yield 'command_in_custom_root_namespace' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeCommand::class),
            [
                // command name
                'app:foo',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeCommandInCustomRootNamespace')
            ->changeRootNamespace('Custom'),
        ];
    }
}