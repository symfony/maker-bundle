<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;

/**
 * @author Antoine Michelet <jean.marcel.michelet@gmail.com>
 */
final class MakeReactApi extends AbstractMaker
{
    private $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public static function getCommandName(): string
    {
        return 'make:react:api';
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(WebpackEncoreBundle::class, 'webpack-encore');
    }

    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates a javascript file containing a set of ajax request (using axios library) for a given api resource')
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        $command->addArgument('name');
        $command->addArgument('api-resource-name');
        $command->addArgument('api-resource-folder');

        $input->setArgument('name',
            $io->askQuestion(new Question('Name of you react application in assets/ (specify if your application is in a subfolder: e.g. js/app_react)', 'app_react'))
        );

        $input->setArgument('api-resource-name',
            $io->ask(sprintf('Choose a name for the api resource %s ', '(e.g. <fg=yellow>product</>)'), null, [Validator::class, 'notBlank'])
        );

        $input->setArgument('api-resource-folder',
            $io->askQuestion(new Question('Choose the name of the folder where to generate the api resource in your react app', 'Api'))
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        if (false === $this->fileManager->fileExists($appPath = 'assets/'.$input->getArgument('name'))) {
            throw new \Exception(sprintf('The react app "%s" does not exists.', $appPath));
        }

        $folder = $input->getArgument('api-resource-folder');
        $resourceName = $input->getArgument('api-resource-name');

        $generator->generateFile(
            $appPath.'/'.$folder.'/Api'.ucfirst($resourceName).'.js',
            'app_react/react/Api/ApiCustomResource.tpl.php', [
                'api_resource_name' => lcfirst($resourceName),
            ]
        );
        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }
}
