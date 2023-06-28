<?php

namespace App\Tests;

use App\Document\Currency;
use App\Document\Invoice;
use App\Document\Money;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GeneratedDocumentTest extends KernelTestCase
{
    public function testGeneratedDocument()
    {
        self::bootKernel();
        /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
        $dm = self::$kernel->getContainer()
            ->get('doctrine_mongodb')
            ->getManager();

        $dm->createQueryBuilder(Invoice::class)
            ->remove()
            ->getQuery()
            ->execute();

        $invoice = new Invoice();
        // check that the constructor was instantiated properly
        $this->assertInstanceOf(Money::class, $invoice->getTotal());
        // fields should now have setters
        $invoice->setTitle('Borscht');

        $total = new Money(100, new Currency('EUR'));
        $invoice->setTotal($total);

        $dm->persist($invoice);

        $dm->flush();
        $dm->refresh($invoice);

        /** @var Invoice[] $actualInvoice */
        $actualInvoice = $dm->getRepository(Invoice::class)
            ->findAll();

        $this->assertcount(1, $actualInvoice);

        /** @var Money $actualTotal */
        $actualTotal = $actualInvoice[0]->getTotal();

        $this->assertInstanceOf(Money::class, $actualTotal);
    }
}
