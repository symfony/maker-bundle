<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeVoter implements MakerInterface
{
    public static function getCommandName(): string
    {
        return 'make:voter';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConf): void
    {
        $command
            ->setDescription('Creates a new security voter class')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the security voter class (e.g. <fg=yellow>BlogPostVoter</>).')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeVoter.txt'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
    }

    public function getParameters(InputInterface $input): array
    {
        $voterClassName = Str::asClassName($input->getArgument('name'), 'Voter');
        Validator::validateClassName($voterClassName);

        return array(
            'voter_class_name' => $voterClassName,
        );
    }

    public function getFiles(array $params): array
    {
        return array(
            __DIR__.'/../Resources/skeleton/security/Voter.php.txt' => 'src/Security/Voter/'.$params['voter_class_name'].'.php',
        );
    }

    public function writeNextStepsMessage(array $params, ConsoleStyle $io): void
    {
        $io->text(array(
            'Next: Open your voter and add your logic.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/security/voters.html</>',
        ));
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Voter::class,
            'security'
        );
    }
}
