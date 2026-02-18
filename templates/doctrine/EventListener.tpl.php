<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

#[AsDoctrineListener(event: <?= $event ?>)]
final class <?= $class_name."\n" ?>
{
    public function <?= $method_name ?>(<?= $event_arg ?>): void
    {
        // ...
    }
}
