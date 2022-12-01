<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker\Security;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Security\InteractiveSecurityHelper;
use Symfony\Bundle\MakerBundle\Security\SecurityConfigUpdater;
use Symfony\Bundle\MakerBundle\Security\SecurityControllerBuilder;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
abstract class AbstractSecurityMaker extends AbstractMaker
{
    protected const SECURITY_CONFIG_PATH = 'config/packages/security.yaml';

    protected YamlSourceManipulator $ysm;
    protected string $securityControllerName;
    protected string $firewallToUpdate;
    protected string $userClass;
    protected string $userNameField;
    protected bool $willLogout;

    public function __construct(
        protected FileManager $fileManager,
        protected SecurityConfigUpdater $securityConfigUpdater,
        protected SecurityControllerBuilder $securityControllerBuilder,
    ) {
    }

    protected function runFixer(string $templateFilePath): void
    {
        $fixerPath = \dirname(__DIR__, 2).'/bin/php-cs-fixer-v3.13.0.phar';
        $configPath = \dirname(__DIR__, 2).'/Resources/test/.php_cs.test';

        $process = Process::fromShellCommandline(sprintf('php %s --config=%s --using-cache=no fix %s', $fixerPath, $configPath, $templateFilePath));
        $process->run();
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(SecurityBundle::class, 'security');
        $dependencies->addClassDependency(Process::class, 'process');
        $dependencies->addClassDependency(Yaml::class, 'yaml');
        $dependencies->addClassDependency(DoctrineBundle::class, 'orm');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (!$this->fileManager->fileExists(self::SECURITY_CONFIG_PATH)) {
            throw new RuntimeCommandException(sprintf('The file "%s" does not exist. PHP & XML configuration formats are currently not supported.', self::SECURITY_CONFIG_PATH));
        }

        $this->securityControllerName = $io->ask(
            'Choose a name for the controller class (e.g. <fg=yellow>ApiLoginController</>)',
            'ApiLoginController',
            [Validator::class, 'validateClassName']
        );

        $this->ysm = new YamlSourceManipulator($this->fileManager->getFileContents(self::SECURITY_CONFIG_PATH));
        $securityData = $this->ysm->getData();

        $securityHelper = new InteractiveSecurityHelper();
        $this->firewallToUpdate = $securityHelper->guessFirewallName($io, $securityData);
        $this->userClass = $securityHelper->guessUserClass($io, $securityData['security']['providers']);
        $this->userNameField = $securityHelper->guessUserNameField($io, $this->userClass, $securityData['security']['providers']);
        $this->willLogout = $io->confirm('Do you want to generate a \'/logout\' URL?');
    }
}
