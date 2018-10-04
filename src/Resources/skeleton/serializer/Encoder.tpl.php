<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Class <?= $class_name ?>
 * @package <?= $namespace; ?>
 */
class <?= $class_name ?> implements EncoderInterface, DecoderInterface
{
    const FORMAT = '<?= $format ?>';

    /**
     * Encodes data into the given format.
     *
     * @param mixed  $data    Data to encode
     * @param string $format  Format name
     * @param array  $context Options that normalizers/encoders have access to
     * @return string|int|float|bool
     * @throws UnexpectedValueException
     */
    public function encode($data, $format, array $context = [])
    {
        // TODO: return your encoded data
        return '';
    }

    /**
     * Checks whether the serializer can encode to given format.
     *
     * @param string $format Format name
     * @return bool
     */
    public function supportsEncoding($format): bool
    {
        return self::FORMAT === $format;
    }

    /**
     * Decodes a string into PHP data.
     *
     * @param string $data    Data to decode
     * @param string $format  Format name
     * @param array  $context Options that decoders have access to
     * @return mixed
     * @throws UnexpectedValueException
     */
    public function decode($data, $format, array $context = [])
    {
        // TODO: return your decoded data
        return '';
    }

    /**
     * Checks whether the deserializer can decode from given format.
     *
     * @param string $format Format name
     * @return bool
     */
    public function supportsDecoding($format): bool
    {
        return self::FORMAT === $format;
    }
}
