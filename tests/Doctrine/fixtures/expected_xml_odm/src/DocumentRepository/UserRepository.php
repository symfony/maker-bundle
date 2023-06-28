<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\DocumentRepository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Document\UserXml;

/**
 * @extends DocumentRepository<UserXml>
 *
 * @method UserXml|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserXml|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserXml[]    findAll()
 * @method UserXml[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $uow = $dm->getUnitOfWork();
        $classMetaData = $dm->getClassMetadata(UserXml::class);
        parent::__construct($dm, $uow, $classMetaData);
    }

    public function save(UserXml $document, bool $flush = false): void
    {
        $this->dm->persist($document);

        if ($flush) {
            $this->dm->flush();
        }
    }

    public function remove(UserXml $document, bool $flush = false): void
    {
        $this->dm->remove($document);

        if ($flush) {
            $this->dm->flush();
        }
    }
}
