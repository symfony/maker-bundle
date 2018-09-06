<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Security;

use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
final class InteractiveSecurityHelper
{
    /**
     * @param InputInterface $input
     * @param SymfonyStyle   $io
     * @param array          $securityData
     */
    public function guessFirewallName(InputInterface $input, SymfonyStyle $io, array $securityData)
    {
        $firewalls = array_filter(
            $securityData['security']['firewalls'] ?? [],
            function ($item) {
                return !isset($item['security']) || true === $item['security'];
            }
        );

        if (count($firewalls) < 2) {
            if ($firewalls) {
                $input->setOption('firewall-name', key($firewalls));
            } else {
                $input->setOption('firewall-name', 'main');
            }

            return;
        }

        $firewallName = $io->choice('Which firewall you want to update ?', array_keys($firewalls), key($firewalls));
        $input->setOption('firewall-name', $firewallName);
    }

    /**
     * @param InputInterface $input
     * @param SymfonyStyle   $io
     * @param Generator      $generator
     * @param array          $securityData
     */
    public function guessEntryPoint(InputInterface $input, SymfonyStyle $io, Generator $generator, array $securityData)
    {
        $firewallName = $input->getOption('firewall-name');
        if (!$firewallName) {
            throw new RuntimeCommandException("Option \"firewall-name\" must be provided.");
        }

        if (!isset($securityData['security'])) {
            $securityData['security'] = [];
        }

        if (!isset($securityData['security']['firewalls'])) {
            $securityData['security']['firewalls'] = [];
        }

        $firewalls = $securityData['security']['firewalls'];
        if (!isset($firewalls[$firewallName])) {
            throw new RuntimeCommandException("Firewall \"$firewallName\" does not exist");
        }

        if (!isset($firewalls[$firewallName]['guard'])
            || !isset($firewalls[$firewallName]['guard']['authenticators'])
            || !$firewalls[$firewallName]['guard']['authenticators']) {
            return;
        }

        $authenticators = $firewalls[$firewallName]['guard']['authenticators'];
        $classNameDetails = $generator->createClassNameDetails(
            $input->getArgument('authenticator-class'),
            'Security\\'
        );
        $authenticators[] = $classNameDetails->getFullName();

        $entryPoint = $io->choice('Which authenticator will be the entry point ?', $authenticators, current($authenticators));
        $input->setOption('entry-point', $entryPoint);
    }
}
