//PHP_OPEN

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
<?php echo $eventUseStatement; ?>
class <?php echo $subscriber_class_name; ?> implements EventSubscriberInterface
{
    public function <?php echo $methodName; ?>(<?php echo $eventArg; ?>)
    {
        // ...
    }

    public static function getSubscribedEvents()
    {
        return [
           '<?php echo $event; ?>' => '<?php echo $methodName; ?>',
        ];
    }
}
