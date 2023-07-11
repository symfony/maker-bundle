<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

/**
 * @extends DocumentRepository<<?= $document_class_name; ?>>
 *
 * @method <?= $document_class_name; ?>|null find($id, $lockMode = null, $lockVersion = null)
 * @method <?= $document_class_name; ?>|null findOneBy(array $criteria, array $orderBy = null)
 * @method <?= $document_class_name; ?>[]    findAll()
 * @method <?= $document_class_name; ?>[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class <?= $class_name; ?> extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $uow = $dm->getUnitOfWork();
        $classMetaData = $dm->getClassMetadata(<?= $document_class_name; ?>::class);
        parent::__construct($dm, $uow, $classMetaData);
    }
<?php if ($include_example_comments): // When adding a new method without existing default comments, the blank line is automatically added.?>

<?php endif; ?>
<?php if ($include_example_comments): ?>
//    /**
//     * @return <?= $document_class_name ?>[] Returns an array of <?= $document_class_name ?> objects
//     */
//    public function findByExampleField($value)
//    {
//        return $this->createQueryBuilder()
//            ->addAnd(['exampleField' => ['$regex' => $value, '$options' => 'i']])
//            ->sort('exampleField', 'ASC')
//            ->limit(10)
//            ->getQuery()
//            ->execute()
//        ;
//    }

//    public function count(): int
//    {
//        $qb = $this->createQueryBuilder();
//        return $qb->count()->getQuery()->execute();
//    }
<?php endif; ?>
}
