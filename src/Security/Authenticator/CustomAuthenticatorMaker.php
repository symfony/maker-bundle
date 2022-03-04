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
use Symfony\Bundle\MakerBundle\Util\YamlManipulationFailedException;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;

class CustomAuthenticatorMaker extends AbstractAuthenticatorMaker
{
    public function getDescription(): string
    {
        return 'Custom authenticator';
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): bool
    {
        if (!$io->confirm('A custom authenticator is only required in advanced use-cases, are you sure your use-case isn\'t covered by one of the built-in authenticators?', true)) {
            $input->setArgument('authenticator-type', null);

            return false;
        }

        if ($io->confirm('Do you want to use a login form?', false)) {
            $input->setArgument('authenticator-type', 'form-login-authenticator');

            return false;
        }

        return $this->baseInteract($input, $io, $command);
    }

    protected function baseInteract(InputInterface $input, ConsoleStyle $io, Command $command): bool
    {
        // authenticator class
        $command->addArgument('authenticator-class', InputArgument::REQUIRED);
        $questionAuthenticatorClass = new Question('The class name of the authenticator to create (e.g. <fg=yellow>AppCustomAuthenticator</>)');
        $questionAuthenticatorClass->setValidator(function ($answer) {
            Validator::notBlank($answer);

            return Validator::classDoesNotExist($this->generator->createClassNameDetails($answer, 'Security\\', 'Authenticator')->getFullName());
        });
        $input->setArgument('authenticator-class', $io->askQuestion($questionAuthenticatorClass));

        parent::interact($input, $io, $command);

        return true;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator, bool $useSecurity52): array
    {
        $this->generator->generateClass(
            $authenticatorClass = $input->getArgument('authenticator-class'),
            sprintf('authenticator/%sEmptyAuthenticator.tpl.php', $useSecurity52 ? 'Security52' : ''),
            [
                'provider_key_type_hint' => $this->getGuardProviderKeyTypeHint(),
                'use_legacy_passport_interface' => $this->shouldUseLegacyPassportInterface($useSecurity52),
            ]
        );

        $securityYamlUpdated = $this->updateSecurityYamlForAuthenticator(
            $input->getOption('firewall-name'),
            $useSecurity52 ? false : $input->getOption('entry-point'),
            $authenticatorClass,
            $input->hasOption('logout-setup') ? $input->getOption('logout-setup') : false,
            $useSecurity52
        );

        $nextTexts = ['Next:'];
        $nextTexts[] = '- Customize your new authenticator.';
        if (!$securityYamlUpdated) {
            $yamlExample = $this->configUpdater->updateForCustomAuthenticator(
                'security: {}',
                'main',
                null,
                $authenticatorClass,
                $logoutSetup,
                $useSecurity52
            );
            $nextTexts[] = "- Your <info>security.yaml</info> could not be updated automatically. You'll need to add the following config manually:\n\n".$yamlExample;
        }
        $nextTexts[] = sprintf('- Finish the redirect "TODO" in the <info>%s::onAuthenticationSuccess()</info> method.', $authenticatorClass);
    }

    /**
     * Calculates the type-hint used for the $provider argument (string or nothing) for Guard.
     */
    protected function getGuardProviderKeyTypeHint(): string
    {
        // doesn't matter: this only applies to non-Guard authenticators
        if (!class_exists(AbstractFormLoginAuthenticator::class)) {
            return '';
        }

        $reflectionMethod = new \ReflectionMethod(AbstractFormLoginAuthenticator::class, 'onAuthenticationSuccess');
        $type = $reflectionMethod->getParameters()[2]->getType();

        if (!$type instanceof \ReflectionNamedType) {
            return '';
        }

        return sprintf('%s ', $type->getName());
    }

    protected function shouldUseLegacyPassportInterface(bool $useSecurity52): bool
    {
        // only applies to new authenticator security
        if (!$useSecurity52) {
            return false;
        }

        // legacy: checking for Symfony 5.2 & 5.3 before PassportInterface deprecation
        $class = new \ReflectionClass(AuthenticatorInterface::class);
        $method = $class->getMethod('authenticate');

        // 5.4 where return type is temporarily removed
        if (!$method->getReturnType()) {
            return false;
        }

        return PassportInterface::class === $method->getReturnType()->getName();
    }

    protected function updateSecurityYamlForAuthenticator(string $firewallName, ?string $entryPoint, string $authenticatorClass, bool $logoutSetup, bool $security52): bool
    {
        $securityYamlSource = $this->getSecurityYamlSource();
        try {
            $newYaml = $this->configUpdater->updateForCustomAuthenticator(
                $securityYamlSource,
                $firewallName,
                $entryPoint,
                $authenticatorClass,
                $logoutSetup,
                $security52
            );
            $this->generator->dumpFile(self::SECURITY_YAML_PATH, $newYaml);

            return true;
        } catch (YamlManipulationFailedException $e) {
        }

        return false;
    }
}
