<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

class <?= $class_name ?> implements EncoderInterface, DecoderInterface
{
    public const FORMAT = '<?= $format ?>';

    public function encode($data, string $format, array $context = []): string
    {
        // TODO: return your encoded data
        return '';
    }

    public function supportsEncoding(string $format, array $context = []): bool
    {
        return self::FORMAT === $format;
    }

    public function decode(string $data, string $format, array $context = [])
    {
        // TODO: return your decoded data
        return '';
    }

    public function supportsDecoding(string $format, array $context = []): bool
    {
        return self::FORMAT === $format;
    }
}
