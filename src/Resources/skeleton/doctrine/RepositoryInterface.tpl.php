<?php

// extends
$extends = ['ObjectRepository'];

if ($with_password_upgrade) {
    $extends[] = 'PasswordUpgraderInterface';
}

$extends = $extends !== [] ? sprintf(' extends %s', implode(', ', $extends)) : '';
?>
<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $entity_full_class_name; ?>;
use Doctrine\Persistence\ObjectRepository;

interface <?= $interface_name; ?><?= $extends; ?>

{
    public function find($id, $lockMode = null, $lockVersion = null): ?<?= $entity_class_name; ?>;<?= "\n" ?>

    public function findOneBy(array $criteria, array $orderBy = null): ?<?= $entity_class_name; ?>;<?= "\n" ?>

    /**
     * @return <?= $entity_class_name; ?>[]
     */
    public function findAll(): array;

    /**
     * @return <?= $entity_class_name; ?>[]
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array;

    public function getClassName(): string;

<?php if ($with_password_upgrade) : ?>
    /**
    * Used to upgrade (rehash) the user's password automatically over time.
    */
    public function upgradePassword(<?= sprintf('%s ', $password_upgrade_user_interface->getShortName()); ?>$user, string $newEncodedPassword): void;
<?php endif ?>
}