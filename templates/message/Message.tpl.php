<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

<?php if ($transport): ?>#[AsMessage('<?= $transport ?>')]<?= "\n" ?><?php endif ?>
final class <?= $class_name."\n" ?>
{
    /*
     * Add whatever properties and methods you need
     * to hold the data for this message class.
     */

    // public function __construct(
    //     public readonly string $name,
    // ) {
    // }
}
