<?= "<?php\n" ?>

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
<?= $eventClass ? "use $eventClass;\n" : '' ?>

class <?= $subscriber_class_name ?> implements EventSubscriberInterface
{
    public function <?= $methodName ?>(<?= $eventArg ?>)
    {
        // ...
    }

    public static function getSubscribedEvents()
    {
        return [
           '<?= $event ?>' => '<?= $methodName ?>',
        ];
    }
}
