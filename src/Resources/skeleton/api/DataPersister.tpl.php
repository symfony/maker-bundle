<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;

final class <?= $class_name; ?> implements ContextAwareDataPersisterInterface
{
    public function supports($data, array $context = []): bool
    {
        // implement your logic to check whether the given data is supported by this data persister
        return true;
    }

    public function persist($data, array $context = [])
    {
        // call your persistence layer to save $data
        return $data;
    }

    public function remove($data, array $context = [])
    {
        // call your persistence layer to delete $data
    }
}
