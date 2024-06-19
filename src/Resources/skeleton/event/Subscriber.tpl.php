<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

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
