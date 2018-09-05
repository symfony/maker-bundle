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

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @internal
 */
final class InteractiveSecurityHelper
{
    public function guessFirewallName(InputInterface $input, ConsoleStyle $io, array $securityData)
    {
        $firewalls = array_filter(
            $securityData['security']['firewalls'],
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
}
