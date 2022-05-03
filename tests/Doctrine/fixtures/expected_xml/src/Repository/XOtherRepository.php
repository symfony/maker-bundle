<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Entity\XOther;

/**
 * @extends ServiceEntityRepository<XOther>
 *
 * @method XOther|null find($id, $lockMode = null, $lockVersion = null)
 * @method XOther|null findOneBy(array $criteria, array $orderBy = null)
 * @method XOther[]    findAll()
 * @method XOther[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XOtherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XOther::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(XOther $entity, bool $flush = false): void
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
    public function remove(XOther $entity, bool $flush = false): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

//    /**
//     * @return XOther[] Returns an array of XOther objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('x')
//            ->andWhere('x.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('x.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?XOther
//    {
//        return $this->createQueryBuilder('x')
//            ->andWhere('x.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
