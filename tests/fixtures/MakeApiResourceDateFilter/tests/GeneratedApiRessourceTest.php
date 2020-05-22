<?php

namespace App\Tests;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GeneratedApiResourceTest extends WebTestCase
{
    public function testGeneratedEntity()
    {
        $client = self::createClient();
        $container = self::$kernel->getContainer();

        /** @var EntityManager $em */
        $em = $container
            ->get('doctrine')
            ->getManager();

        $em->createQuery('DELETE FROM App\\Entity\\Product p')
            ->execute();

        $product = new Product();
        $product->setNameDatetime(new \DateTime('2000-01-01'))
            ->setNameDate(new \DateTime('2000-02-01'))
        ;

        $em->persist($product);
        $em->flush();

        $actualProduct = $em->getRepository(Product::class)
            ->findAll();

        $this->assertcount(1, $actualProduct);

        $client->xmlHttpRequest('GET', '/api/products.jsonld?nameDatetime%5Bbefore%5D=2000-02-01&nameDate%5Bafter%5D=2000-01-01');
        $this->assertStringContainsString('"hydra:totalItems":1', $client->getResponse()->getContent());
        
        $this->assertTrue($client->getResponse()->isSuccessful());
    }
}
