<?= "<?php\n" ?>

namespace App\Repository;

use App\Entity\<?= $entity_class_name ?>;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class <?= $repository_class_name ?> extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, <?= $entity_class_name ?>::class);
    }

    /*
    public function findBySomething($value)
    {
        return $this->createQueryBuilder('<?= $entity_alias ?>')
            ->where('<?= $entity_alias ?>.something = :value')->setParameter('value', $value)
            ->orderBy('<?= $entity_alias ?>.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
}
