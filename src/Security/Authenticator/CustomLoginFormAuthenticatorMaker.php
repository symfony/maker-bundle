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
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Security\SecurityConfigUpdater;
use Symfony\Bundle\MakerBundle\Security\SecurityControllerBuilder;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class CustomLoginFormAuthenticatorMaker extends CustomAuthenticatorMaker
{
    private $doctrineHelper;

    public function __construct(FileManager $fileManager, Generator $generator, SecurityConfigUpdater $configUpdater, DoctrineHelper $doctrineHelper)
    {
        parent::__construct($fileManager, $generator, $configUpdater);

        $this->doctrineHelper = $doctrineHelper;
    }

    public function isAvailable(bool $security52): bool
    {
        return false;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): bool
    {
        if (!$this->baseInteract($input, $io, $command)) {
            return false;
        }

        $this->askControllerClass($input, $io, $command);
        $this->askUsernameField($input, $io, $command, $input->getArgument('user-class'));

        return true;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator, bool $useSecurity52): array
    {
        $userClass = $input->getArgument('user-class');
        $userClassNameDetails = $this->generator->createClassNameDetails('\\'.$userClass, 'Entity\\');
        $userNameField = $input->hasArgument('username-field') ? $input->getArgument('username-field') : null;
        $authenticatorClass = $input->getArgument('authenticator-class');

        $this->generator->generateClass(
            $authenticatorClass,
            sprintf('authenticator/%sLoginFormAuthenticator.tpl.php', $useSecurity52 ? 'Security52' : ''),
            [
                'user_fully_qualified_class_name' => trim($userClassNameDetails->getFullName(), '\\'),
                'user_class_name' => $userClassNameDetails->getShortName(),
                'username_field' => $userNameField,
                'username_field_label' => Str::asHumanWords($userNameField),
                'username_field_var' => Str::asLowerCamelCase($userNameField),
                'user_needs_encoder' => $this->userClassHasEncoder($this->getSecurityData(), $userClass),
                'user_is_entity' => $this->doctrineHelper->isClassAMappedEntity($userClass),
                'provider_key_type_hint' => $this->getGuardProviderKeyTypeHint(),
                'use_legacy_passport_interface' => $this->shouldUseLegacyPassportInterface($useSecurity52),
            ]
        );

        $securityYamlUpdated = $this->updateSecurityYamlForAuthenticator(
            $input->getOption('firewall-name'),
            $input->getOption('entry-point'),
            $authenticatorClass,
            $input->hasOption('logout-setup') ? $input->getOption('logout-setup') : false,
            $useSecurity52
        );

        $logoutSetup = $input->getOption('logout-setup');
        $this->generateController(
            $input->getArgument('controller-class'),
            'authenticator/EmptySecurityController.tpl.php',
            function (SecurityControllerBuilder $securityControllerBuilder, ClassSourceManipulator $manipulator, ClassNameDetails $controllerClassNameDetails) use ($logoutSetup) {
                if (method_exists($controllerClassNameDetails->getFullName(), 'login')) {
                    throw new RuntimeCommandException(sprintf('Method "login" already exists on class %s', $controllerClassNameDetails->getFullName()));
                }

                $securityControllerBuilder->addLoginMethod($manipulator);
                if ($logoutSetup) {
                    $securityControllerBuilder->addLogoutMethod($manipulator);
                }
            }
        );

        $this->generator->generateTemplate(
            'security/login.html.twig',
            'authenticator/login_form.tpl.php',
            [
                'username_field' => $userNameField,
                'username_is_email' => false !== stripos($userNameField, 'email'),
                'username_label' => ucfirst(Str::asHumanWords($userNameField)),
                'logout_setup' => $logoutSetup,
            ]
        );

        $nextTexts = ['Next:'];
        $nextTexts[] = '- Customize your new authenticator.';
        if (!$securityYamlUpdated) {
            $yamlExample = $this->configUpdater->updateForCustomAuthenticator(
                'security: {}',
                $input->getOption('firewall-name'),
                null,
                $authenticatorClass,
                $logoutSetup,
                $useSecurity52
            );
            $nextTexts[] = "- Your <info>security.yaml</info> could not be updated automatically. You'll need to add the following config manually:\n\n".$yamlExample;
        }
        $nextTexts[] = sprintf('- Finish the redirect "TODO" in the <info>%s::onAuthenticationSuccess()</info> method.', $authenticatorClass);
        if (!$this->doctrineHelper->isClassAMappedEntity($userClass)) {
            $nextTexts[] = sprintf('- Review <info>%s::getUser()</info> to make sure it matches your needs.', $authenticatorClass);
        }

        // this only applies to Guard authentication AND if the user does not have a hasher configured
        if (!$useSecurity52 && !$this->userClassHasEncoder($this->getSecurityData(), $userClass)) {
            $nextTexts[] = sprintf('- Check the user\'s password in <info>%s::checkCredentials()</info>.', $authenticatorClass);
        }

        $nextTexts[] = '- Review & adapt the login template: <info>'.$this->fileManager->getPathForTemplate('security/login.html.twig').'</info>.';

        return $nextTexts;
    }

    private function userClassHasEncoder(array $securityData, string $userClass): bool
    {
        $userNeedsEncoder = false;
        $hashersData = $securityData['security']['encoders'] ?? $securityData['security']['encoders'] ?? [];

        foreach ($hashersData as $userClassWithEncoder => $encoder) {
            if ($userClass === $userClassWithEncoder || is_subclass_of($userClass, $userClassWithEncoder) || class_implements($userClass, $userClassWithEncoder)) {
                $userNeedsEncoder = true;
            }
        }

        return $userNeedsEncoder;
    }
}
