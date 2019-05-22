<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $cacheable_interface? "use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;\n": '' ?>
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class <?= $class_name ?> implements NormalizerInterface<?= $cacheable_interface? ", CacheableSupportsMethodInterface\n" : "\n" ?>
{
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, $format = null, array $context = array()): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        // Here: add, edit, or delete some data

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof \App\Entity\BlogPost;
    }
    <?= ($cacheable_interface)? "\npublic function hasCacheableSupportsMethod(): bool\n    {\n        return true;\n    }\n" : "\n"  ?>
}
