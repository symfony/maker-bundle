<?php

namespace App\Repository;

use App\Entity\SweetFood;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SweetFood>
 *
 * @method SweetFood|null find($id, $lockMode = null, $lockVersion = null)
 * @method SweetFood|null findOneBy(array $criteria, array $orderBy = null)
 * @method SweetFood[]    findAll()
 * @method SweetFood[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SweetFoodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SweetFood::class);
    }
}
