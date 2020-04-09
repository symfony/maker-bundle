<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use <?= $message_full_class_name ?>;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class <?= $class_name ?> implements MessageHandlerInterface
{
    public function __invoke(<?= $message_class_name ?> $message)
    {
        // do something with your message
    }
}
