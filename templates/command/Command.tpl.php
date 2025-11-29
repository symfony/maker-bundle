<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

#[AsCommand(
    name: '<?= $command_name; ?>',
    description: 'Add a short description for your command',
)]
class <?= $class_name; ?>
{
    public function __invoke(
        SymfonyStyle $io,
        #[Argument] string $arg1 = null,
        #[Option] bool $option1 = false,
    ): int
    {
        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($option1) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
