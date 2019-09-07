<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Repository;

use Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Entity\XOther;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
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
     * Obtain a reference to an entity for which the identifier is known,
     * without loading that entity from the database.
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/advanced-configuration.html#reference-proxies
     * @param mixed $id
     * @return XOther
     * @throws \Doctrine\ORM\ORMException
     */
    public function getReference($id)
    {
        return $this->getEntityManager()->getReference(XOther::class, $id);
    }

    // /**
    //  * @return XOther[] Returns an array of XOther objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('x')
            ->andWhere('x.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('x.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?XOther
    {
        return $this->createQueryBuilder('x')
            ->andWhere('x.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
