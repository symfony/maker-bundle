<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\DocumentRepository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Document\XOther;

/**
 * @extends DocumentRepository<XOther>
 *
 * @method XOther|null find($id, $lockMode = null, $lockVersion = null)
 * @method XOther|null findOneBy(array $criteria, array $orderBy = null)
 * @method XOther[]    findAll()
 * @method XOther[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XOtherRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $uow = $dm->getUnitOfWork();
        $classMetaData = $dm->getClassMetadata(XOther::class);
        parent::__construct($dm, $uow, $classMetaData);
    }

    public function save(XOther $document, bool $flush = false): void
    {
        $this->dm->persist($document);

        if ($flush) {
            $this->dm->flush();
        }
    }

    public function remove(XOther $document, bool $flush = false): void
    {
        $this->dm->remove($document);

        if ($flush) {
            $this->dm->flush();
        }
    }
}
