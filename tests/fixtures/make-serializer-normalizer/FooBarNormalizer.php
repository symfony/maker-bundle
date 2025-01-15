<?php

namespace App\Serializer\Normalizer;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FooBarNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $normalizer
    ) {
    }

    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        // TODO: add, edit, or delete some data

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        // TODO: return $data instanceof Object
    }

    public function getSupportedTypes(?string $format): array
    {
        // TODO: return [Object::class => true];
    }
}
