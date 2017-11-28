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
        $voterClassName = Str::asClassName($this->input->getArgument('name'), 'Voter');
        Validator::validateClassName($voterClassName);
        $entityClassName = Str::removeSuffix($voterClassName, 'Voter');
        $entityClass = 'App\\Entity\\'.$entityClassName;

        return [
            'voter_class_name' => $voterClassName,
            'entity_class_name' => $entityClassName,
            'entity_class_use_statement' => class_exists($entityClass) ? "use $entityClass;\n" : '',
            'entity_supports_statement' => class_exists($entityClass) ? ' && $subject instanceof '.$entityClassName : '',
        ];
    }

    protected function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/security/Voter.php.txt' => 'src/Security/Voter/'.$params['voter_class_name'].'.php',
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
