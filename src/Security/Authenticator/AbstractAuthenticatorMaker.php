<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Security\Authenticator;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Security\InteractiveSecurityHelper;
use Symfony\Bundle\MakerBundle\Security\SecurityConfigUpdater;
use Symfony\Bundle\MakerBundle\Security\SecurityControllerBuilder;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class AbstractAuthenticatorMaker implements AuthenticatorMakerInterface
{
    protected const SECURITY_YAML_PATH = 'config/packages/security.yaml';

    protected $fileManager;
    protected $generator;
    protected $configUpdater;
    private $interactiveSecurityHelper;

    public function __construct(FileManager $fileManager, Generator $generator, SecurityConfigUpdater $configUpdater)
    {
        $this->fileManager = $fileManager;
        $this->generator = $generator;
        $this->configUpdater = $configUpdater;
        $this->interactiveSecurityHelper = new InteractiveSecurityHelper();
    }

    public function isAvailable(bool $security52): bool
    {
        return true;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): bool
    {
        $securityData = $this->getSecurityData();
        $command->addOption('firewall-name', null, InputOption::VALUE_OPTIONAL);
        $input->setOption('firewall-name', $firewallName = $this->interactiveSecurityHelper->guessFirewallName($io, $securityData));

        $command->addOption('entry-point', null, InputOption::VALUE_OPTIONAL);

        if (!$securityData['security']['enable_authenticator_manager'] ?? false) {
            $input->setOption(
                'entry-point',
                $this->interactiveSecurityHelper->guessEntryPoint($io, $securityData, $input->getArgument('authenticator-class'), $firewallName)
            );
        }

        $command->addArgument('user-class', InputArgument::REQUIRED);
        $input->setArgument(
            'user-class',
            $userClass = $this->interactiveSecurityHelper->guessUserClass($io, $securityData['security']['providers'])
        );

        return true;
    }

    protected function askControllerClass(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command->addArgument('controller-class', InputArgument::REQUIRED);
        $input->setArgument(
            'controller-class',
            $io->ask(
                'Choose a name for the controller class (e.g. <fg=yellow>SecurityController</>)',
                'SecurityController',
                [Validator::class, 'validateClassName']
            )
        );
    }

    protected function askUsernameField(InputInterface $input, ConsoleStyle $io, Command $command, string $userClass): void
    {
        $securityData = $this->getSecurityData();

        $command->addArgument('username-field', InputArgument::REQUIRED);
        $input->setArgument(
            'username-field',
            $this->interactiveSecurityHelper->guessUserNameField($io, $userClass, $securityData['security']['providers'])
        );
    }

    /**
     * @param callable(SecurityControllerBuilder, ClassSourceManipulator, ClassNameDetails): void $configureControllerBuilder
     */
    protected function generateController(string $controllerClass, string $templateName, callable $configureControllerBuilder = null): void
    {
        $controllerClassNameDetails = $this->generator->createClassNameDetails(
            $controllerClass,
            'Controller\\',
            'Controller'
        );

        if (!class_exists($controllerClassNameDetails->getFullName())) {
            $controllerPath = $this->generator->generateController($controllerClassNameDetails->getFullName(), $templateName);
            $controllerSourceCode = $this->generator->getFileContentsForPendingOperation($controllerPath);
        } else {
            $controllerPath = $this->fileManager->getRelativePathForFutureClass($controllerClassNameDetails->getFullName());
            $controllerSourceCode = $this->fileManager->getFileContents($controllerPath);
        }

        $manipulator = new ClassSourceManipulator($controllerSourceCode, true);

        if ($configureControllerBuilder) {
            $securityControllerBuilder = new SecurityControllerBuilder();

            $configureControllerBuilder($securityControllerBuilder, $manipulator, $controllerClassNameDetails);
        }

        $this->generator->dumpFile($controllerPath, $manipulator->getSourceCode());
    }

    protected function getSecurityData(): array
    {
        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents(self::SECURITY_YAML_PATH));

        return $manipulator->getData();
    }

    protected function getSecurityYamlSource(): string
    {
        return $this->fileManager->getFileContents(self::SECURITY_YAML_PATH);
    }
}
