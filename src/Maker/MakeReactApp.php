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
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;

/**
 * @author Antoine Michelet <jean.marcel.michelet@gmail.com>
 */
final class MakeReactApp extends AbstractMaker
{
    private $filesOnlyInSpa = [
        'Api/home',
        'Api/ApiResource',
        'components/footer',
        'components/header',
        'pages/home',
    ];

    private $reactFilesToGenerate = [
        'App.js',
        'index.js',
        'Api/ApiResource.js',
        'components/footer.js',
        'components/header.js',
        'pages/home.js',
        'logo.svg',
        '@styles/app.css',
    ];

    public static function getCommandName(): string
    {
        return 'make:react:app';
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
            ->setDescription('Creates a React application')
            ->addArgument('name', InputArgument::REQUIRED, sprintf('Choose a name for your React application %s ', '(e.g. <fg=yellow>AppReact</>)'))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        $command->addArgument('is-spa');

        $isSpa = null;
        while (null === $isSpa) {
            $question = $io->askQuestion(new Question('Would you like building a single page application? (enter <comment>?</comment> to see the proposed single page application structure)', 'yes'));

            if ('?' === $question) {
                $this->printSinglePageApplicationStructure($input, $io);

                $isSpa = null;
                continue;
            }
            $isSpa = 'yes' === $question || 'y' === $question;

            $input->setArgument('is-spa', $isSpa);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $controllerClassDetails = $generator->createClassNameDetails(
            $input->getArgument('name').'Controller',
            'Controller\\',
            'Controller'
        );

        $generator->generateController(
            $controllerClassDetails->getFullName(),
            'app_react/Controller.tpl.php', [
                'class_name' => $controllerClassDetails->getShortName(),
                'route_path' => Str::asCommand($input->getArgument('name')),
                'route_name' => Str::asRouteName($input->getArgument('name')),
                'template_name' => Str::asRouteName($input->getArgument('name')),
            ]
        );

        $generator->generateTemplate(
            Str::asRouteName($input->getArgument('name')).'/index.html.twig',
            'app_react/index.tpl.php', [
                'app_name' => Str::asTwigVariable($input->getArgument('name')),
            ]
        );

        foreach ($this->reactFilesToGenerate as $filePath) {
            $ext = '.'.explode('.', $filePath)[1];
            $filePath = str_replace($ext, '', $filePath);

            if (\in_array($filePath, $this->filesOnlyInSpa) && false === $input->getArgument('is-spa')) {
                continue;
            }

            $generator->generateFile(
                'assets/'.Str::asRouteName($input->getArgument('name')).'/'.$filePath.$ext,
                'app_react/react/'.$filePath.'.tpl.php', [
                    'app_name' => Str::asTwigVariable($input->getArgument('name')),
                    'app_path' => Str::asCommand($input->getArgument('name')),
                    'is_spa' => $input->getArgument('is-spa'),
                ]
            );
        }
        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    private function printSinglePageApplicationStructure(InputInterface $input, ConsoleStyle $io)
    {
        $io->comment(sprintf('<fg=yellow;options=bold>%s:</>', 'The proposed single page application structure in '.Str::asSnakeCase($input->getArgument('name'))));
        $io->writeln('');

        $io->writeln(sprintf('<fg=blue>%s</>', ' @styles/'));
        $io->writeln('    app.css');
        $io->writeln('');

        $io->writeln(sprintf('<fg=blue>%s</>', ' Api/'));
        $io->writeln('    ApiResource.js');
        $io->writeln('');

        $io->writeln(sprintf('<fg=blue>%s</>', ' components/'));
        $io->writeln('    header.js');
        $io->writeln('    footer.js');
        $io->writeln('');

        $io->writeln(sprintf('<fg=blue>%s</>', ' pages/'));
        $io->writeln('    home.js');
        $io->writeln('');

        $io->writeln(sprintf('<fg=blue>%s</> <comment>%s</>', ' App.js   ', ' * global organization with react-router by default'));
        $io->writeln(sprintf('<fg=blue>%s</> <comment>%s</>', ' index.js ', ' * renders the application in a twig template with react-dom'));
        $io->writeln(sprintf('<fg=blue>%s</>', ' logo.svg'));
        $io->writeln('');
    }
}
