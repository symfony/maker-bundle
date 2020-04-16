<?php echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use <?php echo $entity_full_class_name; ?>;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use <?php echo $doctrine_registry_class; ?>;
<?php echo $with_password_upgrade ? "use Symfony\Component\Security\Core\Exception\UnsupportedUserException;\n" : '' ?>
<?php echo $with_password_upgrade ? "use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;\n" : '' ?>
<?php echo $with_password_upgrade ? "use Symfony\Component\Security\Core\User\UserInterface;\n" : '' ?>

/**
 * @method <?php echo $entity_class_name; ?>|null find($id, $lockMode = null, $lockVersion = null)
 * @method <?php echo $entity_class_name; ?>|null findOneBy(array $criteria, array $orderBy = null)
 * @method <?php echo $entity_class_name; ?>[]    findAll()
 * @method <?php echo $entity_class_name; ?>[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class <?php echo $class_name; ?> extends ServiceEntityRepository<?php echo $with_password_upgrade ? " implements PasswordUpgraderInterface\n" : "\n" ?>
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, <?php echo $entity_class_name; ?>::class);
    }

<?php if ($with_password_upgrade) { ?>
    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof <?php echo $entity_class_name ?>) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

<?php } ?>
    // /**
    //  * @return <?php echo $entity_class_name ?>[] Returns an array of <?php echo $entity_class_name ?> objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('<?php echo $entity_alias; ?>')
            ->andWhere('<?php echo $entity_alias; ?>.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('<?php echo $entity_alias; ?>.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?<?php echo $entity_class_name."\n" ?>
    {
        return $this->createQueryBuilder('<?php echo $entity_alias ?>')
            ->andWhere('<?php echo $entity_alias ?>.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
