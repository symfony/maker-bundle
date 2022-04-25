<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

final class <?= $class_name ?> implements MessageHandlerInterface
{
    public function __invoke(<?= $message_class_name ?> $message)
    {
        // do something with your message
    }
}
