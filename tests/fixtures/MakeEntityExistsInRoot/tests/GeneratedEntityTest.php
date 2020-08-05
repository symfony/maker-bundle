<?php

namespace App\Tests;

use App\Entity\Directory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedEntityTest extends KernelTestCase
{
    public function testGeneratedEntity()
    {
        self::bootKernel();
        /** @var EntityManager $em */
        $em = self::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $em->createQuery('DELETE FROM App\\Entity\\Directory u')->execute();

        $directory = new Directory();
        // check that the constructor was instantiated properly
        $this->assertInstanceOf(ArrayCollection::class, $directory->getChildDirectories());
        // set existing field
        $directory->setName('root');
        $em->persist($directory);

        $subDir = new Directory();
        $subDir->setName('settings');
        $subDir->setParentDirectory($directory);
        $em->persist($subDir);

        // set via the inverse side
        $subDir2 = new Directory();
        $subDir2->setName('fixtures');
        $directory->addChildDirectory($subDir2);
        $em->persist($subDir2);

        $em->flush();
        $em->refresh($directory);

        $actualDirectory = $em->getRepository(Directory::class)
            ->findAll();

        $this->assertCount(3, $actualDirectory);
        $this->assertCount(2, $actualDirectory[0]->getChildDirectories());
    }
}
