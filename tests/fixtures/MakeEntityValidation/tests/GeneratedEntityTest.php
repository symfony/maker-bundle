<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManager;
use App\Entity\User;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GeneratedEntityTest extends KernelTestCase
{
    public function testGeneratedEntity()
    {
        // load up the database
        // create an entity, persist & query

        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = self::$kernel->getContainer()
            ->get('validator');

        $user = new User();
        $user->setName('a');

        $violations = $validator->validate($user);
        $this->assertcount(2, $violations);
    }
}
