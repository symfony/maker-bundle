<?php

namespace App\Tests;

use App\Entity\Author;
use App\Entity\Book;
use App\Form\BookForm;
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
        $author = (new Author())
            ->setName('foo');
        $formData = [
            'title' => 'bar',
            'author' => 0,
        ];

        $form = $this->factory->create(BookForm::class);
        $form->submit($formData);

        $object = new Book();
        $object->setTitle('bar');
        $object->setAuthor($author);
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
                [Book::class, new ClassMetadata(Book::class)],
                [Author::class, new ClassMetadata(Author::class)],
            ])
        ;

        $execute = $this->createMock(Query::class);
        $execute->method('execute')
            ->willReturn([
                (new Author())->setName('foo'),
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
