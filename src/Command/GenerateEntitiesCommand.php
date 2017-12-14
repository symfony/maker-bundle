<?php

namespace Symfony\Bundle\MakerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\Tools\EntityRepositoryGenerator;
use Symfony\Bundle\MakerBundle\Doctrine\EntityGenerator;
use Symfony\Bundle\MakerBundle\Doctrine\DisconnectedMetadataFactory;

class GenerateEntitiesCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'make:doctrine:entities';

    protected function configure()
    {
        $this
            ->setDescription('Generates entity classes and method stubs from your mapping information for flex based project')
            ->addArgument('name', InputArgument::OPTIONAL, 'An entity name')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'The path where to generate entities when it cannot be guessed')
            ->addOption('backup', null, InputOption::VALUE_NONE, 'Backup existing entities files.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $manager = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));

        $name = strtr($input->getArgument('name'), '/', '\\');
        $name = $name ? 'App:'.$name : 'App';

        if (false !== $pos = strpos($name, ':')) {
            $name = $this->getContainer()->get('doctrine')->getAliasNamespace(substr($name, 0, $pos)).'\\'.substr($name, $pos + 1);
        }

        if (class_exists($name)) {
            $output->writeln(sprintf('Generating entity "<info>%s</info>"', $name));
            $metadata = $manager->getClassMetadata($name, $input->getOption('path'));
        } else {
            $output->writeln(sprintf('Generating entities for namespace "<info>%s</info>"', $name));
            $metadata = $manager->getNamespaceMetadata($name, $input->getOption('path'));
        }

        $generator = $this->getEntityGenerator();

        $backupExisting = $input->getOption('backup');
        $generator->setBackupExisting($backupExisting);

        $repoGenerator = new EntityRepositoryGenerator();
        foreach ($metadata->getMetadata() as $m) {
            if ($backupExisting) {
                $basename = substr($m->name, strrpos($m->name, '\\') + 1);
                $output->writeln(sprintf('  > backing up <comment>%s.php</comment> to <comment>%s.php~</comment>', $basename, $basename));
            }
            // Getting the metadata for the entity class once more to get the correct path if the namespace has multiple occurrences
            try {
                $entityMetadata = $manager->getClassMetadata($m->getName(), $input->getOption('path'));
            } catch (\RuntimeException $e) {
                // fall back to the bundle metadata when no entity class could be found
                $entityMetadata = $metadata;
            }

            $output->writeln(sprintf('  > generating <comment>%s</comment>', $m->name));

            $generator->generate(array($m), $entityMetadata->getPath());

            if ($m->customRepositoryClassName && false !== strpos($m->customRepositoryClassName, $metadata->getNamespace())) {
                $repoGenerator->writeEntityRepositoryClass($m->customRepositoryClassName, $metadata->getPath());
            }
        }
    }

    private function getEntityGenerator()
    {
        $entityGenerator = new EntityGenerator();
        $entityGenerator->setGenerateAnnotations(false);
        $entityGenerator->setGenerateStubMethods(true);
        $entityGenerator->setRegenerateEntityIfExists(false);
        $entityGenerator->setUpdateEntityIfExists(true);
        $entityGenerator->setNumSpaces(4);
        $entityGenerator->setAnnotationPrefix('ORM\\');

        return $entityGenerator;
    }
}
