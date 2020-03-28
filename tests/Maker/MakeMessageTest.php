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

use Symfony\Bundle\MakerBundle\Maker\MakeMessage;
use Symfony\Bundle\MakerBundle\Test\MakerTestCase;
use Symfony\Bundle\MakerBundle\Test\MakerTestDetails;
use Symfony\Component\Yaml\Yaml;

class MakeMessageTest extends MakerTestCase
{
    public function getTestDetails()
    {
        yield 'message_basic' => [MakerTestDetails::createTest(
            $this->getMakerInstance(MakeMessage::class),
            [
                'SendWelcomeEmail',
            ])
            ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeMessageBasic')
            // because there is no version compatible with 7.0
            ->setRequiredPhpVersion(70100),
        ];

        yield 'message_with_transport' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeMessage::class),
                [
                    'SendWelcomeEmail',
                    1,
                ]
            )
                ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeMessageWithTransport')
                // because there is no version compatible with 7.0
                ->setRequiredPhpVersion(70100)
                ->assert(
                    function (string $output, string $directory) {
                        $this->assertStringContainsString('Success', $output);

                        $messengerConfig = Yaml::parse(file_get_contents(sprintf('%s/config/packages/messenger.yaml', $directory)));
                        $this->assertArrayHasKey('routing', $messengerConfig['framework']['messenger']);
                        $this->assertArrayHasKey('App\Message\SendWelcomeEmail', $messengerConfig['framework']['messenger']['routing']);
                        $this->assertSame(
                            'async',
                            $messengerConfig['framework']['messenger']['routing']['App\Message\SendWelcomeEmail']
                        );
                    }
                ),
        ];

        yield 'message_with_no_transport' => [
            MakerTestDetails::createTest(
                $this->getMakerInstance(MakeMessage::class),
                [
                    'SendWelcomeEmail',
                    0,
                ]
            )
                ->setFixtureFilesPath(__DIR__.'/../fixtures/MakeMessageWithTransport')
                // because there is no version compatible with 7.0
                ->setRequiredPhpVersion(70100)
                ->assert(
                    function (string $output, string $directory) {
                        $this->assertStringContainsString('Success', $output);

                        $messengerConfig = Yaml::parse(file_get_contents(sprintf('%s/config/packages/messenger.yaml', $directory)));
                        $this->assertArrayNotHasKey('routing', $messengerConfig['framework']['messenger']);
                    }
                ),
        ];
    }
}
