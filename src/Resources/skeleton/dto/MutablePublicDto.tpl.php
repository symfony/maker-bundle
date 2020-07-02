<?= '<?php'.PHP_EOL ?>
<?php use Symfony\Bundle\MakerBundle\Str;

?>

namespace <?= $namespace ?>;

<?php if (isset($entity_full_class_name)): ?>
use <?= $entity_full_class_name ?>;
<?php endif ?>

/**
 * Data transfer object for <?= $entity_class_name ?>.
 */
class <?= $class_name ?>

{
    /**
     * Create DTO, optionally extracting data from a model.
     */
    public function __construct(<?= $entity_class_name ?> $<?= lcfirst($entity_class_name) ?> = null)
    {
        if ($<?= lcfirst($entity_class_name) ?>) {
<?php foreach ($fields as $propertyName => $mapping): ?>
<?php if (false === $mapping['hasGetter']): ?>
            // @todo implement getter on the Entity
            //$this-><?= $propertyName ?> = $<?= lcfirst($entity_class_name) ?>->get<?= Str::asCamelCase($propertyName) ?>();
<?php else : ?>
            $this-><?= $propertyName ?> = $<?= lcfirst($entity_class_name) ?>->get<?= Str::asCamelCase($propertyName) ?>();
<?php endif; ?>
<?php endforeach; ?>
        }
    }
}
