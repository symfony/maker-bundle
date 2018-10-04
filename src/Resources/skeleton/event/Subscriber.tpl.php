<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
<?= $event_full_class_name ? "use $event_full_class_name;\n" : '' ?>

/**
 * Class <?= $class_name ?>
 * @package <?= $namespace; ?>
 */
class <?= $class_name ?> implements EventSubscriberInterface
{
    /**
     * <?= $method_name ?> description
     *
     * @param <?= $event_arg ?>
     */
    public function <?= $method_name ?>(<?= $event_arg ?>)
    {
        // ...
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
           '<?= $event ?>' => '<?= $method_name ?>',
        ];
    }
}
