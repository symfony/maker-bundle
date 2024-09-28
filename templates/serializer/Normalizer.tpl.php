<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

class <?= $class_name ?> implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $normalizer
    ) {
    }

    public function normalize($object, string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        // TODO: add, edit, or delete some data

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
<?php if ($entity_exists): ?>
        return $data instanceof <?= $entity_name ?>;
<?php else: ?>
        // TODO: return $data instanceof Object
<?php endif ?>
    }

    public function getSupportedTypes(?string $format): array
    {
<?php if ($entity_exists): ?>
        return [<?= $entity_name ?>::class => true];
<?php else: ?>
        // TODO: return [Object::class => true];
<?php endif ?>
    }
}
