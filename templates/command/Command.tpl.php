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
<?php foreach ($command_parameters as $parameter) { ?>
        <?= $parameter; ?>,
<?php } ?>
    ): int {
<?php foreach ($command_notes as $note) { ?>
        <?= $note."\n"; ?>
<?php } ?>

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
