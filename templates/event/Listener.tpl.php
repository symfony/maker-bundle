<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

final class <?= $class_name."\n" ?>
{
    #[AsEventListener<?php if (!$class_event): ?>(event: <?= $event ?>)<?php endif ?>]
    public function <?= $method_name ?>(<?= $event_arg ?>): void
    {
        // ...
    }
}
