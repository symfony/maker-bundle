<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Util;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Component\Yaml\Dumper;

/**
 * Manipulate Docker Compose Files.
 *
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 *
 * @final
 */
class ComposeFileManipulator
{
    public const COMPOSE_FILE_VERSION = '3.7';

    /** @var YamlSourceManipulator */
    private $manipulator;

    public function __construct(string $contents)
    {
        if ('' === $contents) {
            $this->manipulator = new YamlSourceManipulator(
                (new Dumper())->dump($this->getBasicStructure(), 2)
            );
        } else {
            $this->manipulator = new YamlSourceManipulator($contents);
        }

        $this->checkComposeFileVersion();
    }

    public function getComposeData(): array
    {
        return $this->manipulator->getData();
    }

    public function getDataString(): string
    {
        return $this->manipulator->getContents();
    }

    public function serviceExists(string $name): bool
    {
        $data = $this->manipulator->getData();

        if (\array_key_exists('services', $data)) {
            return \array_key_exists($name, $data['services']);
        }

        return false;
    }

    public function addDockerService(string $name, array $details): void
    {
        $data = $this->manipulator->getData();

        $data['services'][$name] = $details;

        $this->manipulator->setData($data);
    }

    public function removeDockerService(string $name): void
    {
        $data = $this->manipulator->getData();

        unset($data['services'][$name]);

        $this->manipulator->setData($data);
    }

    public function exposePorts(string $service, array $ports): void
    {
        $portData = [];
        $portData[] = sprintf('%s To allow the host machine to access the ports below, modify the lines below.', YamlSourceManipulator::COMMENT_PLACEHOLDER_VALUE);
        $portData[] = sprintf('%s For example, to allow the host to connect to port 3306 on the container, you would change', YamlSourceManipulator::COMMENT_PLACEHOLDER_VALUE);
        $portData[] = sprintf('%s "3306" to "3306:3306". Where the first port is exposed to the host and the second is the container port.', YamlSourceManipulator::COMMENT_PLACEHOLDER_VALUE);
        $portData[] = sprintf('%s See https://docs.docker.com/compose/compose-file/compose-file-v3/#ports for more information.', YamlSourceManipulator::COMMENT_PLACEHOLDER_VALUE);

        foreach ($ports as $port) {
            $portData[] = $port;
        }

        $data = $this->manipulator->getData();

        $data['services'][$service]['ports'] = $portData;

        $this->manipulator->setData($data);
    }

    public function addVolume(string $service, string $hostPath, string $containerPath): void
    {
        $data = $this->manipulator->getData();

        $data['services'][$service]['volumes'][] = sprintf('%s:%s', $hostPath, $containerPath);

        $this->manipulator->setData($data);
    }

    private function getBasicStructure(string $version = self::COMPOSE_FILE_VERSION): array
    {
        return [
            'version' => $version,
            'services' => [],
        ];
    }

    private function checkComposeFileVersion(): void
    {
        $data = $this->manipulator->getData();

        if (empty($data['version'])) {
            throw new RuntimeCommandException('docker-compose.yaml file version is not set.');
        }

        if (2.0 > (float) $data['version']) {
            throw new RuntimeCommandException(sprintf('docker-compose.yaml version %s is not supported. Please update your docker-compose.yaml file to the latest version.', $data['version']));
        }
    }
}
