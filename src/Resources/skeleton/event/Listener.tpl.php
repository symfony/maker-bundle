<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

final class <?= $class_name."\n" ?>
{
    #[AsEventListener(event: <?= $event ?>)]
    public function <?= $method_name ?>(<?= $event_arg ?>): void
    {
        // ...
    }
}
