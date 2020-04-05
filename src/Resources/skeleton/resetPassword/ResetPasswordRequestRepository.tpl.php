<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use <?= $request_class_full_name ?>;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\Repository\ResetPasswordRequestRepositoryTrait;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;

/**
 * @method <?= $request_class_name ?>|null find($id, $lockMode = null, $lockVersion = null)
 * @method <?= $request_class_name ?>|null findOneBy(array $criteria, array $orderBy = null)
 * @method <?= $request_class_name ?>[]    findAll()
 * @method <?= $request_class_name ?>[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class <?= $class_name ?> extends ServiceEntityRepository implements ResetPasswordRequestRepositoryInterface
{
    use ResetPasswordRequestRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, <?= $request_class_name ?>::class);
    }

    public function createResetPasswordRequest(
        object $user,
        \DateTimeInterface $expiresAt,
        string $selector,
        string $hashedToken
    ): ResetPasswordRequestInterface {
        return new <?= $request_class_name ?>(
            $user,
            $expiresAt,
            $selector,
            $hashedToken
        );
    }
}
