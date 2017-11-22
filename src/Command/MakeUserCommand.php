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

use Doctrine\ORM\Mapping\Column;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class MakeUserCommand extends AbstractCommand
{
    protected static $defaultName = 'make:user';

    public function configure()
    {
        $this
            ->setDescription('Creates a the user doctrine class, the user provider and the user repository')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeUser.txt'))
        ;
    }

    protected function getParameters(): array
    {
        return [];
    }

    protected function getFiles(array $params): array
    {
        return [
            __DIR__.'/../Resources/skeleton/user/User.php.txt' => 'src/Entity/User.php',
            __DIR__.'/../Resources/skeleton/user/UserRepository.php.txt' => 'src/Repository/UserRepository.php',
            __DIR__.'/../Resources/skeleton/user/UserProvider.php.txt' => 'src/Security/UserProvider.php',
        ];
    }

    protected function getResultMessage(array $params): string
    {
        return 'User entity, User provider and user repository has been created successfully.';
    }

    protected function writeNextStepsMessage(array $params, ConsoleStyle $io)
    {
        $io->text([
            'Next: Add more fields to your entity and start using it.',
            'Find the documentation at <fg=yellow>https://symfony.com/doc/current/doctrine.html#creating-an-entity-class</>'
        ]);
    }

    protected function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Column::class,
            'orm'
        );
        $dependencies->addClassDependency(
            UserProviderInterface::class,
            'security'
        );
    }
}
