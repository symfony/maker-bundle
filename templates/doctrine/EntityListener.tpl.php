<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

#[AsEntityListener(event: <?= $event ?>, entity: <?= $entity ?>::class)]
final class <?= $class_name."\n" ?>
{
    public function __invoke(<?= $entity_arg ?>, <?= $event_arg ?>): void
    {
        // ...
    }
}
