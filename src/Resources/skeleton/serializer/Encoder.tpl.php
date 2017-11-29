<?= "<?php\n" ?>

namespace App\Serializer;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

class <?= $encoder_class_name ?> implements EncoderInterface, DecoderInterface
{
    public const FORMAT = '<?= $format ?>';

    public function encode($data, $format, array $context = [])
    {
        // TODO: return your encoded data
        return '';
    }

    public function supportsEncoding($format): bool
    {
        return self::FORMAT === $format;
    }

    public function decode($data, $format, array $context = [])
    {
        // TODO: return your decoded data
        return '';
    }

    public function supportsDecoding($format): bool
    {
        return self::FORMAT === $format;
    }
}
