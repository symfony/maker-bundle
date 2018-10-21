<?= "<?php" . PHP_EOL ?>

namespace <?= $namespace ?>;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
<?= $event_full_class_name ? "use $event_full_class_name;" . PHP_EOL : null ?>

class <?= $class_name ?> implements EventSubscriberInterface
{
    /**
     * <?= $method_name ?> description
     *
     * @param <?= $event_arg . PHP_EOL ?>
     */
    public function <?= $method_name ?>(<?= $event_arg ?>)
    {
        // ...
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
           '<?= $event ?>' => '<?= $method_name ?>',
        ];
    }
}
