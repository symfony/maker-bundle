<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

<?php if ($use_attributes): ?>
#[AsCommand(
    name: '<?= $command_name; ?>',
    description: 'Add a short description for your command',
)]
<?php endif; ?>
class <?= $class_name; ?> extends Command
{
<?php if (!$use_attributes): ?>
    protected static $defaultName = '<?= $command_name; ?>';
    protected static $defaultDescription = 'Add a short description for your command';

<?php endif; ?>
    protected function configure(): void
    {
        $this
<?= $set_description ? "            ->setDescription(self::\$defaultDescription)\n" : '' ?>
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
