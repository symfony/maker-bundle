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
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Security\Authenticator\AuthenticatorMakerInterface;
use Symfony\Bundle\MakerBundle\Security\SecurityConfigUpdater;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Ryan Weaver   <ryan@symfonycasts.com>
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @internal
 */
final class MakeAuthenticator extends AbstractMaker
{
    private $authenticatorMakers;

    private $fileManager;

    private $generator;

    private $useSecurity52 = false;

    public function __construct(iterable $authenticatorMakers, FileManager $fileManager, Generator $generator)
    {
        $this->authenticatorMakers = $authenticatorMakers instanceof \Traversable ? iterator_to_array($authenticatorMakers) : $authenticatorMakers;
        $this->fileManager = $fileManager;
        $this->generator = $generator;
    }

    public static function getCommandName(): string
    {
        return 'make:auth';
    }

    public static function getCommandDescription(): string
    {
        return 'Configures an authenticator of different flavors';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeAuth.txt'));
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (!$this->fileManager->fileExists($path = 'config/packages/security.yaml')) {
            throw new RuntimeCommandException('The file "config/packages/security.yaml" does not exist. This command requires that file to exist so that it can be updated.');
        }
        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($path));
        $securityData = $manipulator->getData();

        // Determine if we should use new security features introduced in Symfony 5.2
        $this->useSecurity52 = $securityData['security']['enable_authenticator_manager'] ?? false;
        if ($this->useSecurity52 && !class_exists(UserBadge::class)) {
            throw new RuntimeCommandException('MakerBundle does not support generating authenticators using the new authenticator system before symfony/security-bundle 5.2. Please upgrade to 5.2 and try again.');
        }

        // authenticator type
        $authenticatorTypeValues = [];
        /** @var AuthenticatorMakerInterface $authenticatorMaker */
        foreach ($this->authenticatorMakers as $authenticatorType => $authenticatorMaker) {
            if (!$authenticatorMaker->isAvailable($this->useSecurity52)) {
                continue;
            }

            $authenticatorTypeValues[$authenticatorMaker->getDescription()] = $authenticatorType;
        }

        $command->addArgument('authenticator-type', InputArgument::REQUIRED);

        $finalMaker = null;
        while (!$finalMaker) {
            if (null === $finalMaker) {
                $authenticatorType = $io->choice(
                    'What style of authentication do you want?',
                    array_keys($authenticatorTypeValues),
                    key($authenticatorTypeValues)
                );
                $input->setArgument('authenticator-type', $authenticatorTypeValues[$authenticatorType]);
            }

            $authenticatorMaker = $this->authenticatorMakers[$input->getArgument('authenticator-type')];
            $finalMaker = $authenticatorMaker->interact($input, $io, $command);
        }

        // todo move
        $isFormAuthenticator = \in_array($input->getArgument('authenticator-type'), ['login-form', 'form-login-authenticator'], true);
        if ($isFormAuthenticator) {
            $this->checkFormAuthenticatorRequirements($securityData);
        }

        if ('http-basic' !== $input->getArgument('authenticator-type')) {
            $command->addOption('logout-setup', null, InputOption::VALUE_NEGATABLE, '', true);
            $input->setOption('logout-setup', $io->confirm('Do you want to generate a \'/logout\' URL?', true));
        }
    }

    private function checkFormAuthenticatorRequirements(array $securityData)
    {
        $missingPackagesMessage = $this->addDependencies([TwigBundle::class => 'twig'], 'Twig must be installed to display the login form');
        if ($missingPackagesMessage) {
            throw new RuntimeCommandException($missingPackagesMessage);
        }

        if (!isset($securityData['security']['providers']) || !$securityData['security']['providers']) {
            throw new RuntimeCommandException('To generate a form login authentication, you must configure at least one entry under "providers" in "security.yaml".');
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $authenticatorType = $input->getArgument('authenticator-type');
        $authenticatorMaker = $this->authenticatorMakers[$authenticatorType];

        $nextMessage = $authenticatorMaker->generate($input, $io, $generator, $this->useSecurity52);

        $this->generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text($nextMessage);
    }

    public function configureDependencies(DependencyBuilder $dependencies, InputInterface $input = null)
    {
        $dependencies->addClassDependency(
            SecurityBundle::class,
            'security'
        );

        // needed to update the YAML files
        $dependencies->addClassDependency(
            Yaml::class,
            'yaml'
        );
    }
}
