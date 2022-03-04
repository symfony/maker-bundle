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
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\YamlManipulationFailedException;
use Symfony\Component\Console\Input\InputInterface;

class HttpBasicAuthenticatorMaker extends AbstractAuthenticatorMaker
{
    public function getDescription(): string
    {
        return 'HTTP basic';
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator, bool $useSecurity52): array
    {
        $securityYamlSource = $this->getSecurityYamlSource();
        $nextTexts = [];
        try {
            $newYaml = $this->configUpdater->updateForAuthenticator(
                $securityYamlSource,
                $input->getOption('firewall-name'),
                'http_basic',
                true,
                $input->hasOption('logout-setup') ? $input->getOption('logout-setup') : false,
                $useSecurity52
            );

            $this->generator->dumpFile(self::SECURITY_YAML_PATH, $newYaml);
        } catch (YamlManipulationFailedException $e) {
            $yamlExample = $this->configUpdater->updateForAuthenticator(
                'security: {}',
                $input->getOption('firewall-name'),
                'http_basic',
                true,
                $input->hasOption('logout-setup') ? $input->getOption('logout-setup') : false,
                $useSecurity52
            );

            $nextTexts[] = 'Next:';
            $nextTexts[] = "- Your <info>security.yaml</info> could not be updated automatically. You'll need to add the following config manually:\n\n".$yamlExample;
        }

        return $nextTexts;
    }
}
