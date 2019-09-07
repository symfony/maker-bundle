<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Repository;

use Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Entity\UserXml;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method UserXml|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserXml|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserXml[]    findAll()
 * @method UserXml[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserXml::class);
    }

    /**
     * Obtain a reference to an entity for which the identifier is known,
     * without loading that entity from the database.
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/advanced-configuration.html#reference-proxies
     * @param mixed $id
     * @return UserXml
     * @throws \Doctrine\ORM\ORMException
     */
    public function getReference($id)
    {
        return $this->getEntityManager()->getReference(UserXml::class, $id);
    }

    // /**
    //  * @return UserXml[] Returns an array of UserXml objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserXml
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
