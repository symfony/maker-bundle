<?= "<?php\n" ?>

namespace App\Repository;

use <?= $token_full_class_name ?>;
use <?= $user_full_class_name ?>;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method <?= $token_class_name ?>|null find($id, $lockMode = null, $lockVersion = null)
 * @method <?= $token_class_name ?>|null findOneBy(array $criteria, array $orderBy = null)
 * @method <?= $token_class_name ?>[]    findAll()
 * @method <?= $token_class_name ?>[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class <?= $class_name ?> extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, <?= $token_class_name ?>::class);
    }

    public function findNonExpiredForUser(<?= $user_class_name ?> $user): array
    {
        // We calculate the oldest datetime a valid token could have generated at
        $tokenLifetime = new \DateInterval(sprintf('PT%sH', <?= $token_class_name ?>::LIFETIME_HOURS));
        $minDateTime = (new \DateTimeImmutable('now'))->sub($tokenLifetime);

        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.requestedAt >= :minDateTime')
            ->setParameters([
                'minDateTime' => $minDateTime,
                'user' => $user,
            ])
            ->getQuery()
            ->getResult()
        ;
    }
}
