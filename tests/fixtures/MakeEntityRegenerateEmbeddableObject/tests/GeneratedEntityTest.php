<?php

namespace App\Tests;

use App\Entity\Currency;
use App\Entity\Invoice;
use App\Entity\Money;
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

        $em->createQuery('DELETE FROM App\\Entity\\Invoice i')->execute();

        $invoice = new Invoice();
        // check that the constructor was instantiated properly
        $this->assertInstanceOf(Money::class, $invoice->getTotal());
        // fields should now have setters
        $invoice->setTitle('Borscht');

        $total = new Money(100, new Currency('EUR'));
        $invoice->setTotal($total);

        $em->persist($invoice);

        $em->flush();
        $em->refresh($invoice);

        /** @var Invoice[] $actualInvoice */
        $actualInvoice = $em->getRepository(Invoice::class)
            ->findAll();

        $this->assertcount(1, $actualInvoice);

        /** @var Money $actualTotal */
        $actualTotal = $actualInvoice[0]->getTotal();

        $this->assertInstanceOf(Money::class, $actualTotal);
    }
}
