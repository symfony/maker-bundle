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
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Maker\Common\InstallDependencyTrait;
use Symfony\Bundle\MakerBundle\Util\DependencyInstaller;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;

class DependencyInstallerTest extends TestCase
{
    public function testSuccessfulInstall()
    {
        $executedCommands = [];
        $installer = new DependencyInstaller(static function (string $command) use (&$executedCommands): Process {
            $executedCommands[] = $command;

            // a real process, running a harmless command instead of composer
            return Process::fromShellCommandline('echo installing');
        });

        $output = new BufferedOutput();
        $installer->installPackage(new ConsoleStyle(new ArrayInput([]), $output), 'symfony/webhook');

        $this->assertSame(['composer require symfony/webhook'], $executedCommands);
        $this->assertStringContainsString('Running: composer require symfony/webhook', $output->fetch());
    }

    public function testFailedInstallThrows()
    {
        $installer = new DependencyInstaller(
            static fn (string $command): Process => Process::fromShellCommandline('echo "could not resolve" && exit 1'),
        );

        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessageMatches('/Could not install "symfony\/webhook":.*could not resolve/s');

        $installer->installPackage(new ConsoleStyle(new ArrayInput([]), new BufferedOutput()), 'symfony/webhook');
    }

    /**
     * Guards the wiring itself: the trait once carried its OWN silent Process
     * call and the loud installer was dead code. This runs the real trait ->
     * real installer -> real composer chain against a package that cannot
     * exist, in a scratch project, and demands the loud failure.
     */
    public function testTraitDelegatesToInstallerAndFailsLoudly()
    {
        $maker = new class {
            use InstallDependencyTrait;
        };

        $io = new ConsoleStyle(new ArrayInput([]), new BufferedOutput());

        // guard branch: the expected class exists, composer must not even run
        $this->assertSame($io, $maker->installDependencyIfNeeded($io, \stdClass::class, 'maker-bundle-test/never-installed'));

        $scratch = sys_get_temp_dir().'/maker-installer-trait-'.getmypid();
        @mkdir($scratch);
        file_put_contents($scratch.'/composer.json', '{}');

        $cwd = getcwd();
        chdir($scratch);

        try {
            $this->expectException(RuntimeCommandException::class);
            $this->expectExceptionMessageMatches('/Could not install "maker-bundle-test\/definitely-not-a-real-package"/');

            $maker->installDependencyIfNeeded($io, 'Maker\Bundle\Test\ClassThatDoesNotExist', 'maker-bundle-test/definitely-not-a-real-package');
        } finally {
            chdir($cwd);
        }
    }
}
