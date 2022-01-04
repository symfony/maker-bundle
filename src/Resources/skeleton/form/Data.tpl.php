<?= "<?php\n" ?>
<?php use Symfony\Bundle\MakerBundle\Str; ?>

namespace <?= $namespace ?>;

<?php if (isset($bounded_full_class_name)): ?>
use <?= $bounded_full_class_name ?>;
<?php endif ?>

<?php if (isset($addImportUid['uuid']) && $addImportUid['uuid']): ?>
use Symfony\Component\Uid\Uuid;
<?php elseif (isset($addImportUid['ulid']) && $addImportUid['ulid']): ?>
use Symfony\Component\Uid\Ulid;
<?php endif ?>

/**
 * Data transfer object for <?= $bounded_class_name ?>.
 */
class <?= $class_name ?>

{
<?php if ($addHelpers): ?>
    /**
     * Create DTO, optionally extracting data from a model.
     *
     * @param <?= $bounded_class_name ?>|null $<?= lcfirst($bounded_class_name) ?>

     */
    public function __construct(?<?= $bounded_class_name ?> $<?= lcfirst($bounded_class_name) ?> = null)
    {
    }

    /**
    * Create DTO empty
    */
    public static function createEmpty(): self
    {
        return new self();
    }

    public static function createFrom<?= $bounded_class_name ?>(<?= $bounded_class_name ?> $<?= lcfirst($bounded_class_name) ?>): self
    {
        $dto = new self();

<?php foreach($fields as $propertyName => $mapping): ?>
        $dto-><?= $propertyName ?> = $<?= lcfirst($bounded_class_name) ?>->get<?= Str::asCamelCase($propertyName) ?>();
<?php endforeach; ?>

        return $dto;
    }

<?php endif; ?>
}
