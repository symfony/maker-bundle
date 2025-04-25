<?php

namespace App\Tests;

use App\Entity\Librarian;
use App\Entity\Library;
use App\Form\LibraryForm;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class GeneratedFormTest extends TypeTestCase
{
    public function testGeneratedForm()
    {
        $librarian = (new Librarian())
            ->setName('foo');
        $formData = [
            'name' => 'bar',
            'librarian' => 0,
        ];

        $form = $this->factory->create(LibraryForm::class);
        $form->submit($formData);

        $object = new Library();
        $object->setName('bar');
        $object->setLibrarian($librarian);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($object, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    protected function getExtensions(): array
    {
        $mockEntityManager = $this->createMock(EntityManager::class);
        $mockEntityManager->method('getClassMetadata')
            ->willReturnMap([
                [Library::class, new ClassMetadata(Library::class)],
                [Librarian::class, new ClassMetadata(Librarian::class)],
            ])
        ;

        $execute = $this->createMock(Query::class);
        $execute->method('execute')
            ->willReturn([
                (new Librarian())->setName('foo'),
            ]);

        $query = $this->createMock(QueryBuilder::class);
        $query->method('getQuery')
            ->willReturn($execute);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('createQueryBuilder')
            ->willReturn($query)
        ;

        $mockEntityManager->method('getRepository')->willReturn($entityRepository);

        $mockRegistry = $this->createMock(ManagerRegistry::class);
        $mockRegistry->method('getManagerForClass')
            ->willReturn($mockEntityManager)
        ;
        $mockRegistry->method('getManagers')
            ->willReturn([$mockEntityManager])
        ;

        return array_merge(parent::getExtensions(), [new DoctrineOrmExtension($mockRegistry)]);
    }
}
