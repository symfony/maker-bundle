<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

#[AsMessageHandler]
final class <?= $class_name ?>
{
    public function __invoke(<?= $message_class_name ?> $message): void
    {
        // do something with your message
    }
}
