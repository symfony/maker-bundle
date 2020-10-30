<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
<?= isset($resource_class_name) ? "use $resource_class_name;\n" : '' ?>

final class <?= $class_name; ?> implements ContextAwareDataPersisterInterface
{
<?php if (isset($is_doctrine_persister) && isset($resource_short_name)): ?>
    private $decorated;

    public function __construct(ContextAwareDataPersisterInterface $decorated)
    {
        $this->decorated = $decorated;
    }
    
<?php endif ?>
    public function supports($data, array $context = []): bool
    {
<?php if (isset($is_doctrine_persister) && isset($resource_short_name)): ?>
        return $this->decorated->supports($data, $context);
<?php elseif (isset($resource_short_name)):?>
        return $data instanceof <?= $resource_short_name; ?>;
<?php else: ?>
        return true;
<?php endif ?>
    }

    public function persist($data, array $context = [])
    {
<?php if (isset($is_doctrine_persister) && isset($resource_short_name)): ?>
        $data = $this->decorated->persist($data, $context);

<?php endif ?>
        return $data;
    }

    public function remove($data, array $context = [])
    {
<?php if (isset($is_doctrine_persister) && isset($resource_short_name)): ?>
        return $this->decorated->persist($data, $context);
<?php endif ?>
    }
}
