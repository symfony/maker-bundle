<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

class <?= $class_name ?> implements EventSubscriberInterface
{
    public function <?= $method_name ?>(<?= $event_arg ?>)
    {
        // ...
    }

    public static function getSubscribedEvents()
    {
        return [
            <?= $event ?> => '<?= $method_name ?>',
        ];
    }
}
