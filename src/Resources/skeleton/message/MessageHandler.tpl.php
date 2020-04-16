<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

use <?php echo $message_full_class_name ?>;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class <?php echo $class_name ?> implements MessageHandlerInterface
{
    public function __invoke(<?php echo $message_class_name ?> $message)
    {
        // do something with your message
    }
}
