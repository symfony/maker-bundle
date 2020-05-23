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
        $product->setIsAvailableGenericallyInMyCountry(true)
            ->setSold(1)
            ->setPrice(10)
            ->setTransportFees('abc')
        ;

        $em->persist($product);

        $product2 = new Product();
        $product2->setIsAvailableGenericallyInMyCountry(true)
            ->setSold(1)
            ->setPrice(10)
            ->setTransportFees('bcd')
        ;

        $em->persist($product2);

        $em->flush();

        $actualProduct = $em->getRepository(Product::class)
            ->findAll();

        $this->assertcount(2, $actualProduct);

        $client->xmlHttpRequest(
            'GET', '/api/products.jsonld?isAvailableGenericallyInMyCountry=true&sold=1&price[between]=0..20&exists[transportFees]=true&order[transportFees]=desc'
        );

        $jsonExpected = '"hydra:member":[{"@id":"\/api\/products\/2","@type":"Product","id":2,"isAvailableGenericallyInMyCountry":true,"sold":1,"price":10,"transportFees":"bcd"},{"@id":"\/api\/products\/1","@type":"Product","id":1,"isAvailableGenericallyInMyCountry":true,"sold":1,"price":10,"transportFees":"abc"}],"hydra:totalItems":2';

        $this->assertStringContainsString($jsonExpected, $client->getResponse()->getContent());
        
        $this->assertTrue($client->getResponse()->isSuccessful());
    }
}
