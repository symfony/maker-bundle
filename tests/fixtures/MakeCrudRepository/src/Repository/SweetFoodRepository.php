<?php

namespace App\Repository;

use App\Entity\SweetFood;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method SweetFood|null find($id, $lockMode = null, $lockVersion = null)
 * @method SweetFood|null findOneBy(array $criteria, array $orderBy = null)
 * @method SweetFood[]    findAll()
 * @method SweetFood[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SweetFoodRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, SweetFood::class);
    }

    /*
    public function findBySomething($value)
    {
        return $this->createQueryBuilder('t')
            ->where('t.something = :value')->setParameter('value', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
}
