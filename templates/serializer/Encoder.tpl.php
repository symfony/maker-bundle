<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

class <?= $class_name ?> implements EncoderInterface, DecoderInterface
{
    public const FORMAT = '<?= $format ?>';

    public function encode(mixed $data, string $format, array $context = []): string
    {
        // TODO: return your encoded data
        return '';
    }

    public function supportsEncoding(string $format): bool
    {
        return self::FORMAT === $format;
    }

    public function decode(string $data, string $format, array $context = [])<?php if ($use_decoder_return_type): ?>: mixed<?php endif; ?>
    {
        // TODO: return your decoded data
        return '';
    }

    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }
}
