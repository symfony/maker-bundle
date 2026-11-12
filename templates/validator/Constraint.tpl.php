<?= "<?php\n" ?>

namespace <?= $class_data->getNamespace(); ?>;

<?= $class_data->getUseStatements(); ?>

<?php if ('class' === $target): ?>
#[\Attribute(\Attribute::IS_REPEATABLE)]
<?php else: ?>
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
<?php endif; ?>
<?= $class_data->getClassDeclaration(); ?>

{
    public string $message = 'The string "{{ value }}" contains an illegal character: it can only contain letters or numbers.';

    // You can use #[HasNamedArguments] to make some constraint options required.
    // All configurable options must be passed to the constructor.
    public function __construct(
        public string $mode = 'strict',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }
<?php if ('class' === $target): ?>

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
<?php endif; ?>
}
