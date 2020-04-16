<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

<?php echo ($cacheable_interface = interface_exists('Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface')) ? "use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;\n" : '' ?>
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class <?php echo $class_name ?> implements NormalizerInterface<?php echo $cacheable_interface ? ', CacheableSupportsMethodInterface' : '' ?><?php echo "\n" ?>
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
<?php if ($cacheable_interface) { ?>

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
<?php } ?>
}
