<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $entity_full_class_name; ?>;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class <?= $class_name; ?>
 * @package <?= $namespace; ?>
 * @method <?= $entity_class_name; ?>|null find($id, $lockMode = null, $lockVersion = null)
 * @method <?= $entity_class_name; ?>|null findOneBy(array $criteria, array $orderBy = null)
 * @method <?= $entity_class_name; ?>[]    findAll()
 * @method <?= $entity_class_name; ?>[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class <?= $class_name; ?> extends ServiceEntityRepository
{
    /**
     * <?= $class_name; ?> constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, <?= $entity_class_name; ?>::class);
    }

//    /**
//     * @param mixed $value
//     * @return <?= $entity_class_name ?>[] Returns an array of <?= $entity_class_name ?> objects
//     */
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

//    /**
//     * @param mixed $value
//     * @return <?= $entity_class_name ?>
//     */
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
}
