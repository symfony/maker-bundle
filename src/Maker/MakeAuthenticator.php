<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Security\InteractiveSecurityHelper;
use Symfony\Bundle\MakerBundle\Security\SecurityConfigUpdater;
use Symfony\Bundle\MakerBundle\Util\YamlManipulationFailedException;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * @author Ryan Weaver <ryan@knpuniversity.com>
 *
 * @internal
 */
final class MakeAuthenticator extends AbstractMaker
{
    private $fileManager;

    private $configUpdater;

    private $generator;

    public function __construct(FileManager $fileManager, SecurityConfigUpdater $configUpdater, Generator $generator)
    {
        $this->fileManager = $fileManager;
        $this->configUpdater = $configUpdater;
        $this->generator = $generator;
    }

    public static function getCommandName(): string
    {
        return 'make:auth';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates an empty Guard authenticator')
            ->addArgument('authenticator-class', InputArgument::OPTIONAL, 'The class name of the authenticator to create (e.g. <fg=yellow>AppCustomAuthenticator</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeAuth.txt'));
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (!$this->fileManager->fileExists($path = 'config/packages/security.yaml')) {
            return;
        }

        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($path));
        $securityData = $manipulator->getData();

        $interactiveSecurityHelper = new InteractiveSecurityHelper();

        $command->addOption('firewall-name', null, InputOption::VALUE_OPTIONAL, '');
        $input->setOption('firewall-name', $firewallName = $interactiveSecurityHelper->guessFirewallName($io, $securityData));

        $command->addOption('entry-point', null, InputOption::VALUE_OPTIONAL);

        $authenticatorClassNameDetails = $this->generator->createClassNameDetails(
            $input->getArgument('authenticator-class'),
            'Security\\'
        );

        $input->setOption(
            'entry-point',
            $interactiveSecurityHelper->guessEntryPoint($io, $securityData, $authenticatorClassNameDetails->getFullName(), $firewallName)
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $classNameDetails = $generator->createClassNameDetails(
            $input->getArgument('authenticator-class'),
            'Security\\'
        );

        $generator->generateClass(
            $classNameDetails->getFullName(),
            'authenticator/Empty.tpl.php',
            []
        );

        $securityYamlUpdated = false;
        $path = 'config/packages/security.yaml';
        if ($this->fileManager->fileExists($path)) {
            try {
                $newYaml = $this->configUpdater->updateForAuthenticator(
                    $this->fileManager->getFileContents($path),
                    $input->getOption('firewall-name'),
                    $input->getOption('entry-point'),
                    $classNameDetails->getFullName()
                );
                $generator->dumpFile($path, $newYaml);
                $securityYamlUpdated = true;
            } catch (YamlManipulationFailedException $e) {
            }
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $text = ['Next: Customize your new authenticator.'];
        if (!$securityYamlUpdated) {
            $yamlExample = $this->configUpdater->updateForAuthenticator(
                'security: {}',
                'main',
                null,
                $classNameDetails->getFullName()
            );
            $text[] = "Your <info>security.yaml</info> could not be updated automatically. You'll need to add the following config manually:\n\n".$yamlExample;
        }
        $io->text($text);
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            SecurityBundle::class,
            'security'
        );
    }
}
