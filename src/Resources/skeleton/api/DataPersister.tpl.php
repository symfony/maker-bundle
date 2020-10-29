<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
<?= isset($resource_class_name) ? "use $resource_class_name;\n" : '' ?>

final class <?= $class_name; ?> implements ContextAwareDataPersisterInterface
{
    public function supports($data, array $context = []): bool
    {
        // implement your logic to check whether the given data is supported by this data persister
        <?= isset($resource_short_name) ? "return \$data instanceof $resource_short_name;" : "return true;" ?> 
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