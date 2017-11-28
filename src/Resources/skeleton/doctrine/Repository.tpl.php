//PHP_OPEN

namespace App\Repository;

use App\Entity\<?php echo $entity_class_name; ?>;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class <?php echo $repository_class_name; ?> extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, <?php echo $entity_class_name; ?>::class);
    }

    /*
    public function findBySomething($value)
    {
        return $this->createQueryBuilder('<?php echo $entity_alias; ?>')
            ->where('<?php echo $entity_alias; ?>.something = :value')->setParameter('value', $value)
            ->orderBy('<?php echo $entity_alias; ?>.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
}
