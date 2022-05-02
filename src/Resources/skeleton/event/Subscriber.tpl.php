<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
<?= $event_full_class_name ? "use $event_full_class_name;\n" : '' ?>

class <?= $class_name ?> implements EventSubscriberInterface
{
    public function <?= $method_name ?>(<?= $event_arg ?>): void
    {
        // ...
    }

    public static function getSubscribedEvents(): array
    {
        return [
            <?= $event ?> => '<?= $method_name ?>',
        ];
    }
}
