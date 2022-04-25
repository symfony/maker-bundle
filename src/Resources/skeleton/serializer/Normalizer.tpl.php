<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

class <?= $class_name ?> implements NormalizerInterface<?= $cacheable_interface ? ', CacheableSupportsMethodInterface' : '' ?><?= "\n" ?>
{
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        // Here: add, edit, or delete some data

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof \App\Entity\<?= str_replace('Normalizer', null, $class_name) ?>;
    }
<?php if ($cacheable_interface): ?>

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
<?php endif; ?>
}
