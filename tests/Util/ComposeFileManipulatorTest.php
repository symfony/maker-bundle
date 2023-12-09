<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Util\ComposeFileManipulator;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class ComposeFileManipulatorTest extends TestCase
{
    public function testComposeFileVersion(): void
    {
        self::assertSame('3.7', ComposeFileManipulator::COMPOSE_FILE_VERSION);
    }

    public function testGetComposeDataReturnsEmptyComposeFileOnEmpty(): void
    {
        $manipulator = new ComposeFileManipulator('');

        $expected = [
            'version' => '3.7',
            'services' => [],
        ];

        self::assertSame($expected, $manipulator->getComposeData());
    }

    public function testServiceExists(): void
    {
        $composeFile = <<< 'EOT'
            version: '3.7'
            services:
                database:
            EOT;
        $manipulator = new ComposeFileManipulator($composeFile);

        self::assertTrue($manipulator->serviceExists('database'));
        self::assertFalse($manipulator->serviceExists('redis'));
    }

    public function testAddDockerService(): void
    {
        $manipulator = new ComposeFileManipulator('');
        $manipulator->addDockerService('redis', ['coming' => 'soon']);

        $expected = [
            'version' => '3.7',
            'services' => [
                'redis' => [
                    'coming' => 'soon',
                ],
            ],
        ];

        self::assertSame($expected, $manipulator->getComposeData());
    }

    public function testRemoveDockerService(): void
    {
        $composeFile = <<< 'EOT'
            version: '3.7'
            services:
                database:
                rabbitmq:
            EOT;
        $manipulator = new ComposeFileManipulator($composeFile);
        $manipulator->removeDockerService('rabbitmq');

        $expected = [
            'version' => '3.7',
            'services' => [
                'database' => null,
            ],
        ];

        self::assertSame($expected, $manipulator->getComposeData());
    }

    public function testExposePorts(): void
    {
        $composeFile = <<< 'EOT'
            version: '3.7'
            services:
                rabbitmq:
                    am: 'I next?'
            EOT;
        $manipulator = new ComposeFileManipulator($composeFile);
        $manipulator->exposePorts('rabbitmq', ['15672']);

        $expected = [
            'version' => '3.7',
            'services' => [
                'rabbitmq' => [
                    'am' => 'I next?',
                    'ports' => [
                        '15672',
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $manipulator->getComposeData());
    }

    public function testAddVolume(): void
    {
        $composeFile = <<< 'EOT'
            version: '3.7'
            services:
                php:
                    yes: 'this looks fun'
            EOT;

        $manipulator = new ComposeFileManipulator($composeFile);
        $manipulator->addVolume('php', '/var/htdocs', '/var');

        $expected = [
            'version' => '3.7',
            'services' => [
                'php' => [
                    'yes' => 'this looks fun',
                    'volumes' => [
                        '/var/htdocs:/var',
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $manipulator->getComposeData());
    }

    public function testCheckComposeFileVersion(): void
    {
        new ComposeFileManipulator('version: \'2\'');

        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('compose.yaml version 1.9 is not supported. Please update your compose.yaml file to the latest version.');

        new ComposeFileManipulator('version: \'1.9\'');
    }

    public function testCheckComposeFileVersionThrowsExceptionWithMissingVersion(): void
    {
        $composeFile = <<< 'EOT'
            services:
                []
            EOT;
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage('compose.yaml file version is not set.');

        new ComposeFileManipulator($composeFile);
    }
}
