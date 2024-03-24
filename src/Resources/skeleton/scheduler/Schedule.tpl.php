<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

#[AsSchedule]
final class <?= $class_name; ?> implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
<?php if ($has_custom_message): ?>
            // @TODO - Modify the frequency to suite your needs
            RecurringMessage::every('1 hour', new <?= $message_class_name; ?>()),
<?php else: ?>
            // @TODO - Create a Message to schedule
            // RecurringMessage::every('1 hour', new App\Message\Message()),
<?php endif ?>
            )
            ->stateful($this->cache)
        ;
    }
}
