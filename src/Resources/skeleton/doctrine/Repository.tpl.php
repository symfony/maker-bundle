<?php

// Build the required variables for building the class header.
// Class doc
$classDoc = '';
if (false === $use_standalone_services) {
    $classDoc = <<<EOF
/**
 * @method ${entity_class_name}|null find(\$id, \$lockMode = null, \$lockVersion = null)
 * @method ${entity_class_name}|null findOneBy(array \$criteria, array \$orderBy = null)
 * @method ${entity_class_name}[]    findAll()
 * @method ${entity_class_name}[]    findBy(array \$criteria, array \$orderBy = null, \$limit = null, \$offset = null)
 */
EOF;
}

// Final keyword.
$final = '';
if ($use_standalone_services) {
    $final = 'final ';
}

// extends
$extends = false === $use_standalone_services ? ' extends ServiceEntityRepository' : '';

// interfaces
$interfaces = [];
if ($with_password_upgrade) {
    $interfaces[] = 'PasswordUpgraderInterface';
}
if ($use_standalone_services) {
    $interfaces[] = $interface_name;
}
$interfaces = $interfaces !== [] ? sprintf(' implements %s', implode(', ', $interfaces)) : '';
?>
<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $entity_full_class_name; ?>;
<?php if (true === $use_standalone_services) : ?>
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
<?php else : ?>
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use <?= $doctrine_registry_class; ?>;
<?php endif ?>
<?= $with_password_upgrade ? "use Symfony\Component\Security\Core\Exception\UnsupportedUserException;\n" : '' ?>
<?= ($with_password_upgrade && str_contains($password_upgrade_user_interface->getFullName(), 'Password')) ? sprintf("use %s;\n", $password_upgrade_user_interface->getFullName()) : null ?>
<?= $with_password_upgrade ? "use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;\n" : '' ?>
<?= ($with_password_upgrade && str_contains($password_upgrade_user_interface->getFullName(), '\UserInterface')) ? sprintf("use %s;\n", $password_upgrade_user_interface->getFullName()) : null ?>

<?= $classDoc; ?>

<?= $final; ?>class <?= $class_name; ?><?= $extends; ?><?= $interfaces; ?>

{
<?php if (false === $use_standalone_services) : ?>
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, <?= $entity_class_name; ?>::class);
    }
<?php else : ?>
    private EntityManagerInterface $entityManager;

    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(<?= $entity_class_name; ?>::class);
    }
<?php endif; ?>
<?php if (true === $use_standalone_services) : ?>
    public function find($id, $lockMode = null, $lockVersion = null): ?<?= $entity_class_name; ?><?= "\n" ?>
    {
        return $this->repository->find($id, $lockMode, $lockVersion);
    }

    public function findOneBy(array $criteria, array $orderBy = null): ?<?= $entity_class_name; ?><?= "\n" ?>
    {
        return $this->repository->findOneBy($criteria, $orderBy);
    }

    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): array
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }

    public function getClassName(): string
    {
        return <?= $entity_class_name; ?>::class;
    }
<?php endif; ?>
<?php if ($with_password_upgrade) : ?>
    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(<?= sprintf('%s ', $password_upgrade_user_interface->getShortName()); ?>$user, string $newEncodedPassword): void
    {
        if (!$user instanceof <?= $entity_class_name ?>) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
    <?php if (true === $use_standalone_services) : ?>
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    <?php else : ?>
        $this->_em->persist($user);
        $this->_em->flush();
    <?php endif ?>
    }
<?php endif ?>
<?php if ($include_example_comments) : ?>

    // /**
    //  * @return <?= $entity_class_name ?>[] Returns an array of <?= $entity_class_name ?> objects
    //  */
    /*
    public function findByExampleField($value): array
    {
    <?php if (true === $use_standalone_services) : ?>
    return $this->repository->createQueryBuilder('<?= $entity_alias; ?>')
    <?php else : ?>
    return $this->createQueryBuilder('<?= $entity_alias; ?>')
    <?php endif ?>
        ->andWhere('<?= $entity_alias; ?>.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('<?= $entity_alias; ?>.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
    ;
    }
    */

    /*
    public function findOneBySomeField($value): ?<?= $entity_class_name . "\n" ?>
    {
    <?php if (true === $use_standalone_services) : ?>
    return $this->repository->createQueryBuilder('<?= $entity_alias; ?>')
    <?php else : ?>
    return $this->createQueryBuilder('<?= $entity_alias; ?>')
    <?php endif ?>
        ->andWhere('<?= $entity_alias ?>.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
    ;
    }
    */
<?php endif; ?>
}