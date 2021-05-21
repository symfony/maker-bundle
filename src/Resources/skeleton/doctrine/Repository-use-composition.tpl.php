<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $entity_full_class_name; ?>;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
<?= $with_password_upgrade ? "use Symfony\Component\Security\Core\Exception\UnsupportedUserException;\n" : '' ?>
<?= ($with_password_upgrade && str_contains($password_upgrade_user_interface->getFullName(), 'Password')) ? sprintf("use %s;\n", $password_upgrade_user_interface->getFullName()) : null ?>
<?= $with_password_upgrade ? "use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;\n" : '' ?>
<?= ($with_password_upgrade && str_contains($password_upgrade_user_interface->getFullName(), '\UserInterface')) ? sprintf("use %s;\n", $password_upgrade_user_interface->getFullName()) : null ?>

final class <?= $class_name; ?><?= $with_password_upgrade ? " implements PasswordUpgraderInterface\n" : "\n" ?>
{
    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(<?= $entity_class_name; ?>::class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?<?= $entity_class_name; ?><?= "\n"?>
    {
        return $this->repository->find($id, $lockMode, $lockVersion);
    }

    public function findOneBy(array $criteria, array $orderBy = null): ?<?= $entity_class_name; ?><?= "\n"?>
    {
        return $this->repository->findOneBy($criteria, $orderBy);
    }

    /**
     * @return <?= $entity_class_name; ?>[]
     */
    public function findAll(): iterable
    {
        return $this->repository->findAll();
    }

    /**
     * @return <?= $entity_class_name; ?>[]
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): iterable
    {
        return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
    }
<?php if ($include_example_comments): // When adding a new method without existing default comments, the blank line is automatically added.?>

<?php endif; ?>
<?php if ($with_password_upgrade): ?>
    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(<?= sprintf('%s ', $password_upgrade_user_interface->getShortName()); ?>$user, string $newEncodedPassword): void
    {
        if (!$user instanceof <?= $entity_class_name ?>) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

<?php endif ?>
<?php if ($include_example_comments): ?>
    // /**
    //  * @return <?= $entity_class_name ?>[] Returns an array of <?= $entity_class_name ?> objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->repository->createQueryBuilder('<?= $entity_alias; ?>')
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
    public function findOneBySomeField($value): ?<?= $entity_class_name."\n" ?>
    {
        return $this->repository->createQueryBuilder('<?= $entity_alias ?>')
            ->andWhere('<?= $entity_alias ?>.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
<?php endif; ?>
}
