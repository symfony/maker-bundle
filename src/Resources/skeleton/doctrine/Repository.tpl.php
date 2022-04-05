<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $entity_full_class_name; ?>;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use <?= $doctrine_registry_class; ?>;
<?= $with_password_upgrade ? "use Symfony\Component\Security\Core\Exception\UnsupportedUserException;\n" : '' ?>
<?= ($with_password_upgrade && str_contains($password_upgrade_user_interface->getFullName(), 'Password')) ? sprintf("use %s;\n", $password_upgrade_user_interface->getFullName()) : null ?>
<?= $with_password_upgrade ? "use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;\n" : '' ?>
<?= ($with_password_upgrade && str_contains($password_upgrade_user_interface->getFullName(), '\UserInterface')) ? sprintf("use %s;\n", $password_upgrade_user_interface->getFullName()) : null ?>

/**
 * @extends ServiceEntityRepository<<?= $entity_class_name; ?>>
 *
 * @method <?= $entity_class_name; ?>|null find($id, $lockMode = null, $lockVersion = null)
 * @method <?= $entity_class_name; ?>|null findOneBy(array $criteria, array $orderBy = null)
 * @method <?= $entity_class_name; ?>[]    findAll()
 * @method <?= $entity_class_name; ?>[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class <?= $class_name; ?> extends ServiceEntityRepository<?= $with_password_upgrade ? " implements PasswordUpgraderInterface\n" : "\n" ?>
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, <?= $entity_class_name; ?>::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(<?= $entity_class_name ?> $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(<?= $entity_class_name ?> $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
<?php if ($include_example_comments): // When adding a new method without existing default comments, the blank line is automatically added.?>

<?php endif; ?>
<?php if ($with_password_upgrade): ?>
    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(<?= sprintf('%s ', $password_upgrade_user_interface->getShortName()); ?>$user, string $newHashedPassword): void
    {
        if (!$user instanceof <?= $entity_class_name ?>) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
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
        return $this->createQueryBuilder('<?= $entity_alias; ?>')
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
        return $this->createQueryBuilder('<?= $entity_alias ?>')
            ->andWhere('<?= $entity_alias ?>.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
<?php endif; ?>
}
