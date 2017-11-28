<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Command;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bundle\SecurityBundle\SecurityBundle;

/**
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
final class MakeAuthenticatorCommand extends AbstractCommand
{
    protected static $defaultName = 'make:auth';

    public function configure()
    {
        $this
            ->setDescription('Creates an empty Guard authenticator')
            ->addArgument('authenticator-class', InputArgument::OPTIONAL, 'The class name of the authenticator to create (e.g. <fg=yellow>AppCustomAuthenticator</>)')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeAuth.txt'))
        ;
    }

    protected function getParameters(): array
    {
        $className = Str::asClassName($this->input->getArgument('authenticator-class'));
        Validator::validateClassName($className);

        return [
            'class_name' => $className,
        ];
    }

    protected function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/authenticator/Empty.php.txt' => 'src/Security/'.$params['class_name'].'.php',
        ];
    }

    protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Customize your new authenticator.',
            'Then, configure the "guard" key on your firewall to use it.'
        ]);
    }

    protected function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            SecurityBundle::class,
            'security'
        );
    }
}
