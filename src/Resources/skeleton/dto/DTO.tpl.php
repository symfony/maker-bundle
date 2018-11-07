<?= '<?php'.PHP_EOL ?>
<?php use Symfony\Bundle\MakerBundle\Str;

?>

namespace <?= $namespace ?>;

<?php if (isset($bounded_full_class_name)): ?>
use <?= $bounded_full_class_name ?>;
<?php endif ?>
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data transfer object for <?= $bounded_class_name ?>.
 */
class <?= $class_name ?>

{
<?php if ($addHelpers): ?>
    /**
     * Create DTO, optionally extracting data from a model.
     */
    public function __construct(?<?= $bounded_class_name ?> $<?= lcfirst($bounded_class_name) ?> = null)
    {
        if (null !== $<?= lcfirst($bounded_class_name) ?>) {
            $this->extract($<?= lcfirst($bounded_class_name) ?>);
        }
    }

    /**
     * Fill entity with data from the DTO.
     */
    public function fill(<?= $bounded_class_name ?> $<?= lcfirst($bounded_class_name) ?>): <?= $bounded_class_name ?><?= PHP_EOL ?>
    {
<?php if ($omitGettersSetters): ?>
<?php foreach ($fields as $propertyName => $mapping): ?>
<?php if (false === $mapping['hasSetter']): ?>
        // @todo implement setter on the Entity
        //$<?= lcfirst($bounded_class_name) ?>->set<?= Str::asCamelCase($propertyName) ?>($this-><?= $propertyName ?>);
<?php else : ?>
        $<?= lcfirst($bounded_class_name) ?>->set<?= Str::asCamelCase($propertyName) ?>($this-><?= $propertyName ?>);
<?php endif; ?>
<?php endforeach; ?>
<?php else : ?>
<?php foreach ($fields as $propertyName => $mapping): ?>
<?php if (false === $mapping['hasSetter']): ?>
        // @todo implement setter on the Entity
        //$<?= lcfirst($bounded_class_name) ?>->set<?= Str::asCamelCase($propertyName) ?>($this->get<?= Str::asCamelCase($propertyName) ?>());
<?php else : ?>
        $<?= lcfirst($bounded_class_name) ?>->set<?= Str::asCamelCase($propertyName) ?>($this->get<?= Str::asCamelCase($propertyName) ?>());
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>

        return $<?= lcfirst($bounded_class_name) ?>;
    }

    /**
     * Extract data from entity into the DTO.
     */
    public function extract(<?= $bounded_class_name ?> $<?= lcfirst($bounded_class_name) ?>): self
    {
<?php if ($omitGettersSetters): ?>
<?php foreach ($fields as $propertyName => $mapping): ?>
<?php if (false === $mapping['hasGetter']): ?>
        // @todo implement getter on the Entity
        //$this-><?= $propertyName ?> = $<?= lcfirst($bounded_class_name) ?>->get<?= Str::asCamelCase($propertyName) ?>();
<?php else : ?>
        $this-><?= $propertyName ?> = $<?= lcfirst($bounded_class_name) ?>->get<?= Str::asCamelCase($propertyName) ?>();
<?php endif; ?>
<?php endforeach; ?>
<?php else : ?>
<?php foreach ($fields as $propertyName => $mapping): ?>
<?php if (false === $mapping['hasGetter']): ?>
        // @todo implement getter on the Entity
        //$this->set<?= Str::asCamelCase($propertyName) ?>($<?= lcfirst($bounded_class_name) ?>->get<?= Str::asCamelCase($propertyName) ?>());
<?php else : ?>
        $this->set<?= Str::asCamelCase($propertyName) ?>($<?= lcfirst($bounded_class_name) ?>->get<?= Str::asCamelCase($propertyName) ?>());
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>

        return $this;
    }
<?php endif; ?>
}
