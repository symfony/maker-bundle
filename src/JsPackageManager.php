<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class JsPackageManager
{
    private $executableFinder;
    private $files;

    public function __construct(FileManager $fileManager)
    {
        $this->executableFinder = new ExecutableFinder();
        $this->files = $fileManager;
    }

    public function add(string $package, string $version): void
    {
        $packageWithVersion = "{$package}@{$version}";

        if ($yarn = $this->executableFinder->find('yarn')) {
            $command = [$yarn, 'add', $packageWithVersion, '--dev'];
        } elseif ($npm = $this->executableFinder->find('npm')) {
            $command = [$npm, 'install', $packageWithVersion, '--save-dev'];
        } else {
            $this->addToPackageJson($package, $version);

            return;
        }

        (new Process($command, $this->files->getRootDirectory()))->run();
    }

    public function install(): void
    {
        (new Process([$this->bin(), 'install'], $this->files->getRootDirectory()))->run();
    }

    public function run(string $script): void
    {
        (new Process([$this->bin(), 'run', $script], $this->files->getRootDirectory()))->run();
    }

    public function isAvailable(): bool
    {
        try {
            $this->bin();

            return true;
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    private function bin(): string
    {
        if (!$bin = $this->executableFinder->find('yarn') ?? $this->executableFinder->find('npm')) {
            throw new \RuntimeException('Unable to find js package manager.');
        }

        return $bin;
    }

    private function addToPackageJson(string $package, string $version): void
    {
        $packageJson = json_decode($this->files->getFileContents('package.json'), true);
        $devDeps = $packageJson['devDependencies'] ?? [];
        $devDeps[$package] = $version;

        ksort($devDeps);

        $packageJson['devDependencies'] = $devDeps;

        $this->files->dumpFile('package.json', json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
