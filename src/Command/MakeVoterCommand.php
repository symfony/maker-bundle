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
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
final class MakeVoterCommand extends AbstractCommand
{
    protected static $defaultName = 'make:voter';

    public function configure()
    {
        $this
            ->setDescription('Creates a new security voter class')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the security voter class (e.g. <fg=yellow>BlogPostVoter</>).')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeVoter.txt'))
        ;
    }

    protected function getParameters(): array
    {
        $name = $this->input->getArgument('name');
        if(empty($name)) {
            throw new RuntimeCommandException("You must provide the name of the voter you want to create");
        }

        $voterClassName = Str::asClassName($name, 'Voter');
        Validator::validateClassName($voterClassName);

        return [
            'voter_class_name' => $voterClassName,
        ];
    }

    protected function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/security/Voter.php.txt' => 'src/Security/Voter/'.$params['voter_class_name'].'.php'
        ];
    }

    protected function getResultMessage(array $params): string
    {
        return sprintf('<fg=blue>%s</> created successfully.', $params['voter_class_name']);
    }

    protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Open your voter and add your logic.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/security/voters.html</>'
        ]);
    }

    protected function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Voter::class,
            'security'
        );
    }
}
