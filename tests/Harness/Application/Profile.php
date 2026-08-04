<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\Harness\Application;

final class Profile
{
    /**
     * @param array<string, array{reason: string, version?: string}>                        $require
     * @param array<string, array{reason: string, version?: string}>                        $requireDev
     * @param list<array{file: string, find: string, replace: string, allowMissing?: bool}> $patches
     * @param array<string, string>                                                         $files      app-relative target => absolute source path, copied at build time
     * @param list<list<string>>                                                            $postBuild  commands (argv arrays) run inside the built profile
     *                                                                                                  e.g. panther's browser-driver download happens HERE,
     *                                                                                                  at build time, never at test time
     */
    public function __construct(
        public string $name,
        public ?string $extends = null,
        public array $require = [],
        public array $requireDev = [],
        public array $patches = [],
        public array $files = [],
        public array $postBuild = [],
        public string $rootNamespace = 'App',
    ) {
    }

    /**
     * @return list<string> composer requirement strings, e.g. "phpunit/phpunit:^11.5"
     */
    public function composerRequirements(bool $dev): array
    {
        $requirements = [];
        foreach ($dev ? $this->requireDev : $this->require as $package => $config) {
            $requirements[] = isset($config['version']) ? $package.':'.$config['version'] : $package;
        }

        return $requirements;
    }

    /**
     * @return array<string, mixed>
     *
     * Serializable definition used in the profile fingerprint. Injected files
     * contribute their CONTENT, so editing them rebuilds the profile.
     */
    public function definition(): array
    {
        return [
            'name' => $this->name,
            'extends' => $this->extends,
            'require' => $this->require,
            'requireDev' => $this->requireDev,
            'patches' => $this->patches,
            'files' => array_map(static fn (string $source) => md5_file($source), $this->files),
            'postBuild' => $this->postBuild,
            'rootNamespace' => $this->rootNamespace,
        ];
    }
}
