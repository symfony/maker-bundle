<?= "<?php" . PHP_EOL ?>

namespace <?= $namespace; ?>;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

class <?= $class_name ?> implements EncoderInterface, DecoderInterface
{
    const FORMAT = '<?= $format ?>';

    /**
     * {@inheritdoc}
     */
    public function encode($data, $format, array $context = [])
    {
        // TODO: return your encoded data
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding($format): bool
    {
        return self::FORMAT === $format;
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = [])
    {
        // TODO: return your decoded data
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format): bool
    {
        return self::FORMAT === $format;
    }
}
