<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
<?php echo $event_full_class_name ? "use $event_full_class_name;\n" : '' ?>

class <?php echo $class_name ?> implements EventSubscriberInterface
{
    public function <?php echo $method_name ?>(<?php echo $event_arg ?>)
    {
        // ...
    }

    public static function getSubscribedEvents()
    {
        return [
            <?php echo $event ?> => '<?php echo $method_name ?>',
        ];
    }
}
