<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ResetPasswordInput;
use App\Entity\ResetPasswordRequest;

class <?= $class_name ?> implements DataTransformerInterface
{
    public function transform($object, string $to, array $context = []): object
    {
        return $object;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof ResetPasswordRequest) {
            return false;
        }

        return ResetPasswordRequest::class === $to && ($context['input']['class'] ?? null) === ResetPasswordInput::class;
    }
}
