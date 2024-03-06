<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements ?>

class <?= $class_name ?> extends AbstractTypeExtension
{
    /**
     * Returns an array of extended types.
     */
    public static function getExtendedTypes(): iterable
    {
        return [
            <?= $extended_type ?>::class,
        ];
    }
}
