<?= "<?php\n" ?>
<?php use Symfony\Bundle\MakerBundle\Str; ?>

namespace <?= $namespace ?>;

use <?= $entity_full_class_name ?>;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class <?= $class_name ?> extends WebTestCase<?= "\n" ?>
{
    private static ?KernelBrowser $client = null;

    private ?EntityManager $entityManager;

    protected function setUp(): void
    {
        if (null === self::$client) {
            self::$client = static::createClient();
        }

        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testIndex()
    {
        $client = self::$client;
        $crawler = $client->request('GET', '<?= $route_path ?>/');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('<?= $entity_class_name ?> index', $crawler->filter('h1')->text());
    }

    /**
     * @TODO implement new test
     */
    public function testNew()
    {
        $client = self::$client;
        $crawler = $client->request('GET', '<?= $route_path ?>/');
        $newLink = $crawler->filter('a:contains("Create new")')->eq(0)->link();
        $crawler = $client->click($newLink);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('Create new <?= $entity_class_name ?>', $crawler->filter('h1')->text());

        $form = $crawler->selectButton('Save')->form();

        // // @TODO Set valid values for your fields
<?php foreach ($form_fields as $form_field => $typeOptions): ?>
        // $form['<?= $route_name ?>[<?= $form_field ?>]']->setValue('New ipsum');
<?php endforeach; ?>
        // $client->submit($form);

        // $this->assertTrue($client->getResponse()->isRedirect());

        // $crawler = $client->followRedirect();
        // $this->assertTrue($client->getResponse()->isSuccessful());
        // $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        // $this->assertContains('<?= $entity_class_name ?> index', $crawler->filter('h1')->text());
        // $this->assertContains('New ipsum', $client->getResponse()->getContent());
    }

    // /**
    //  * @TODO implement edit test
    //  */
    // public function testEdit()
    // {
    //     $<?= $entity_var_singular ?> = new <?= $entity_class_name ?>;
<?php foreach ($entity_fields as $propertyName => $mapping): ?>
    //     $<?= lcfirst($entity_var_singular) ?>->set<?= Str::asCamelCase($propertyName) ?>('Edit ipsum');
<?php endforeach; ?>
    //     $this->entityManager->persist($<?= $entity_var_singular ?>);
    //     $this->entityManager->flush();
    //
    //     $client = self::$client;
    //     $crawler = $client->request('GET', '<?= $route_path ?>/');
<?php if(!empty($entity_fields)):?>
    //     $editLink = $crawler->filter('tr:contains('.$<?= lcfirst($entity_var_singular) ?>->get<?= Str::asCamelCase(array_keys($entity_fields)[0]) ?>().') a:contains("edit")')->eq(0)->link();
<?php else: ?>
    //     $editLink = $crawler->filter('tr:contains('Edit ipsum') a:contains("edit")')->eq(0)->link();
<?php endif; ?>
    //
    //     $crawler = $client->click($editLink);
    //     $this->assertSame(200, $client->getResponse()->getStatusCode());
    //     $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
    //     $this->assertContains('Edit <?= $entity_class_name ?>', $crawler->filter('h1')->text());
<?php if(!empty($entity_fields)):?>
    //     $this->assertGreaterThan(0, $crawler->filter('input')->count());
<?php endif; ?>
    //
    //     $form = $crawler->selectButton('Update')->form();
    //     // @TODO Set valid values for your fields
<?php foreach ($form_fields as $form_field => $typeOptions): ?>
    //     $form['<?= $route_name ?>[<?= $form_field ?>]']->setValue('Edited ipsum');
<?php endforeach; ?>
    //     $client->submit($form);
    //     $this->assertTrue($client->getResponse()->isRedirect());
    //
    //     $crawler = $client->followRedirect();
    //     $this->assertTrue($client->getResponse()->isSuccessful());
    //     $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
    //     $this->assertContains('Edited ipsum', $client->getResponse()->getContent());
    // }

    // /**
    //  * @TODO implement show test
    //  */
    // public function testShow()
    // {
    //     $<?= $entity_var_singular ?> = new <?= $entity_class_name ?>;
<?php foreach ($entity_fields as $propertyName => $mapping): ?>
    //     $<?= lcfirst($entity_var_singular) ?>->set<?= Str::asCamelCase($propertyName) ?>('Lorem ipsum');
<?php endforeach; ?>
    //     $this->entityManager->persist($<?= $entity_var_singular ?>);
    //     $this->entityManager->flush();
    //
    //     $client = self::$client;
    //     $crawler = $client->request('GET', '<?= $route_path ?>/');
<?php if(!empty($entity_fields)):?>
    //     $showLink = $crawler->filter('tr:contains('.$<?= lcfirst($entity_var_singular) ?>->get<?= Str::asCamelCase(array_keys($entity_fields)[0]) ?>().') a:contains("show")')->eq(0)->link();
<?php else: ?>
    //     $showLink = $crawler->filter('tr:contains('Edit ipsum') a:contains("show")')->eq(0)->link();
<?php endif; ?>
    //     $crawler = $client->click($showLink);
    //     $this->assertSame(200, $client->getResponse()->getStatusCode());
    //     $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
    //     $this->assertContains('<?= $entity_class_name ?>', $crawler->filter('h1')->text());
<?php if(!empty($entity_fields)):?>
    //     $this->assertContains($<?= lcfirst($entity_var_singular) ?>->get<?= Str::asCamelCase(array_keys($entity_fields)[0]) ?>(), $client->getResponse()->getContent());
<?php else: ?>
    //     $this->assertContains('Lorem ipsum', $client->getResponse()->getContent());
<?php endif; ?>
    // }

    // /**
    //  * @TODO implement delete test
    //  */
    // public function testDelete()
    // {
    //     $<?= $entity_var_singular ?> = new <?= $entity_class_name ?>;
<?php foreach ($entity_fields as $propertyName => $mapping): ?>
    //     $<?= lcfirst($entity_var_singular) ?>->set<?= Str::asCamelCase($propertyName) ?>('Delete ipsum');
<?php endforeach; ?>
    //     $this->entityManager->persist($<?= $entity_var_singular ?>);
    //     $this->entityManager->flush();
    //
    //     $client = self::$client;
    //     $crawler = $client->request('GET', '<?= $route_path ?>/');
<?php if(!empty($entity_fields)):?>
    //     $showLink = $crawler->filter('tr:contains('.$<?= lcfirst($entity_var_singular) ?>->get<?= Str::asCamelCase(array_keys($entity_fields)[0]) ?>().') a:contains("show")')->eq(0)->link();
<?php else: ?>
    //     $showLink = $crawler->filter('tr:contains('Delete ipsum') a:contains("show")')->eq(0)->link();
<?php endif; ?>
    //
    //     $crawler = $client->click($showLink);
    //     $this->assertSame(200, $client->getResponse()->getStatusCode());
    //     $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
    //     $this->assertContains('<?= $entity_class_name ?>', $crawler->filter('h1')->text());
<?php if(!empty($entity_fields)):?>
    //     $this->assertContains($<?= lcfirst($entity_var_singular) ?>->get<?= Str::asCamelCase(array_keys($entity_fields)[0]) ?>(), $crawler->filter('body')->text());
<?php else: ?>
    //     $this->assertContains('Delete ipsum', $crawler->filter('body')->text());
<?php endif; ?>
    //
    //     $form = $crawler->selectButton('Delete')->form();
    //     $client->submit($form);
    //     $this->assertTrue($client->getResponse()->isRedirect());
    //
    //     $crawler = $client->followRedirect();
    //     $this->assertTrue($client->getResponse()->isSuccessful());
    //     $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
    //     $this->assertContains('<?= $entity_class_name ?> index', $crawler->filter('h1')->text());
<?php if(!empty($entity_fields)):?>
    //     $this->assertNotContains($<?= lcfirst($entity_var_singular) ?>->get<?= Str::asCamelCase(array_keys($entity_fields)[0]) ?>(), $client->getResponse()->getContent());
<?php else: ?>
    //     $this->assertNotContains('Delete ipsum', $client->getResponse()->getContent());
<?php endif; ?>
    // }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }
}
