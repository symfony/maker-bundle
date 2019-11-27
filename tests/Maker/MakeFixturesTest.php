<?php

namespace Symfony\Bundle\MakerBundle\Tests\Maker;

use Symfony\Bundle\MakerBundle\Maker\MakeFixtures;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;

class MakeFixturesTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'fixtures' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeFixtures::class),
            [
                'FooFixtures',
            ])
            ->assert(function (string $output, string $directory) {
                $this->assertStringContainsString('created: src/DataFixtures/FooFixtures.php', $output);
            }),
        ];
    }
}
