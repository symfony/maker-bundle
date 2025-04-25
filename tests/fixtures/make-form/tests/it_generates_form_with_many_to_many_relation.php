<?php

namespace App\Tests;

use App\Entity\Library;
use App\Entity\Book;
use App\Form\BookForm;
use Doctrine\Common\Collections\ArrayCollection;
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
    /**
     * @dataProvider provideFormData
     */
    public function testGeneratedFormWithMultipleChoices($formData, $collection)
    {
        $form = $this->factory->create(BookForm::class);
        $form->submit($formData);

        $object = new Book();
        $object->setTitle('foobar');
        $object->setLibraries($collection);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($object, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function provideFormData(): iterable
    {
        yield 'test_submit_with_single_choice_selected' =>
        [
            [
                'title' => 'foobar',
                'libraries' => [1],
            ],
            new ArrayCollection([
                (new Library())->setName('bar'),
            ]),
        ];
        yield ['test_submit_with_multiple_choices_selected' =>
            [
                'title' => 'foobar',
                'libraries' => [0, 1],
            ],
            new ArrayCollection([
                (new Library())->setName('foo'),
                (new Library())->setName('bar'),
            ]),
        ];
        yield ['test_submit_with_no_choice_selected' =>
            [
                'title' => 'foobar',
                'libraries' => [],
            ],
            new ArrayCollection([]),
        ];
    }

    protected function getExtensions(): array
    {
        $mockEntityManager = $this->createMock(EntityManager::class);
        $mockEntityManager->method('getClassMetadata')
            ->willReturnMap([
                [Book::class, new ClassMetadata(Book::class)],
                [Library::class, new ClassMetadata(Library::class)],
            ]);

        $execute = $this->createMock(Query::class);
        $execute->method('execute')
            ->willReturn([
                (new Library())->setName('foo'),
                (new Library())->setName('bar'),
            ]);

        $query = $this->createMock(QueryBuilder::class);
        $query->method('getQuery')
            ->willReturn($execute);


        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->method('createQueryBuilder')
            ->willReturn($query);

        $mockEntityManager->method('getRepository')->willReturn($entityRepository);

        $mockRegistry = $this->createMock(ManagerRegistry::class);
        $mockRegistry->method('getManagerForClass')
            ->willReturn($mockEntityManager);
        $mockRegistry->method('getManagers')
            ->willReturn([$mockEntityManager]);

        return array_merge(parent::getExtensions(), [new DoctrineOrmExtension($mockRegistry)]);
    }
}
