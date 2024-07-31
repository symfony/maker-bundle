<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Contracts\EventDispatcher\Event;
<?php foreach ($useClasses as $useClass): ?>
use <?= $useClass; ?>;
<?php endforeach; ?>

final class <?= $class_name ?> extends Event
{
    public function __construct(
    <?php foreach ($fields as $field): ?>
        <?= $field['visibility'] ?> readonly <?php if ($field['nullable']): ?>?<?php endif; ?><?= $field['type'] ?> $<?= $field['name'] ?>,
    <?php endforeach; ?>
    ) {}
}