<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class <?= $class_name ?> extends WebTestCase<?= "\n" ?>
{
    public function testIndex()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '<?= $route_path ?>/');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('<?= $entity_class_name ?> index', $crawler->filter('h1')->text());

        $newLink = $crawler->filter('a:contains("Create new")')->eq(0)->link();

        return $newLink;
    }

    /**
     * @depends testIndex
     */
    public function testNew($newLink)
    {
        $client = static::createClient();
        $crawler = $client->click($newLink);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        $this->assertContains('Create new <?= $entity_class_name ?>', $crawler->filter('h1')->text());

        $form = $crawler->selectButton('Save')->form();

        // @TODO Set valid values for your fields
<?php foreach ($form_fields as $form_field): ?>
		// $form['<?= $route_name ?>[<?= $form_field ?>]']->setValue('Lorem ipsum');
<?php endforeach; ?>
        // $client->submit($form);

        // $this->assertTrue($client->getResponse()->isRedirect());

        // $crawler = $client->followRedirect();
        // $this->assertTrue($client->getResponse()->isSuccessful());
        // $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
        // $this->assertContains('<?= $entity_class_name ?> index', $crawler->filter('h1')->text());
        // $this->assertContains('<td>Lorem ipsum</td>', $client->getResponse()->getContent());
    }

    // @TODO implement edit test
    // /**
    //  * @depends testNew
    //  */
    // public function testEdit()
    // {
    //      $client = static::createClient();
    //      $crawler = $client->request('GET', '<?= $route_path ?>/');
    //
    //      $this->assertSame(200, $client->getResponse()->getStatusCode());
    //      $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
    //      $this->assertContains('<?= $entity_class_name ?> index', $crawler->filter('h1')->text());
    //
    //      $editLink = $crawler->filter('a:contains("edit")')->eq(0)->link();
    //
    //      $crawler = $client->click($editLink);
    //      $this->assertSame(200, $client->getResponse()->getStatusCode());
    //      $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
    //      $this->assertContains('Edit <?= $entity_class_name ?>', $crawler->filter('h1')->text());
    //      $this->assertGreaterThan(0, $crawler->filter('input[type=text]')->count());
    //
    //      $editForm = $crawler->selectButton('Update')->form();
	// 		@TODO Set valid values for your fields
<?php foreach ($form_fields as $form_field): ?>
	// 		$form['<?= $route_name ?>[<?= $form_field ?>]']->setValue('Lorem ipsum edited');
<?php endforeach; ?>
    // 		$client->submit($form);
    //      $this->assertTrue($client->getResponse()->isRedirect());
    //
    //      $crawler = $client->followRedirect();
    //      $this->assertTrue($client->getResponse()->isSuccessful());
    //      $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
    //      $this->assertContains('<?= $entity_class_name ?> index', $crawler->filter('h1')->text());
    //      $this->assertContains('Lorem ipsum edited', $client->getResponse()->getContent());
    //
    //      $showLink = $crawler->filter('a:contains("show")')->eq(0)->link();
    //      return $showLink;
    // }

    // @TODO implement show test
    // /**
    //  * @depends testEdit
    //  */
    // public function testShow($showLink)
    // {
    //      $client = static::createClient();
    //      $crawler = $client->click($showLink);
    //      $this->assertSame(200, $client->getResponse()->getStatusCode());
    //      $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
    //      $this->assertContains('<?= $entity_class_name ?>', $crawler->filter('h1')->text());
    //      $this->assertContains('Lorem ipsum edited', $crawler->filter('body')->text());
    //
    //      return $showLink;
    // }

    // @TODO implement delete test
    // /**
    //  * @depends testShow
    //  */
    // public function testDelete($showLink)
    // {
    //      $client = static::createClient();
    //      $crawler = $client->click($showLink);
    //      $this->assertSame(200, $client->getResponse()->getStatusCode());
    //      $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
    //      $this->assertContains('<?= $entity_class_name ?>', $crawler->filter('h1')->text());
    //      $this->assertContains('Lorem ipsum edited', $crawler->filter('body')->text());
    //
    //      $deleteForm = $crawler->selectButton('Delete')->form();
    //      $client->submit($deleteForm);
    //      $this->assertTrue($client->getResponse()->isRedirect());
    //
    //      $client->followRedirect();
    //      $this->assertTrue($client->getResponse()->isSuccessful());
    //      $this->assertContains('<!DOCTYPE html>', $client->getResponse()->getContent());
    //      $this->assertContains('<?= $entity_class_name ?> index', $crawler->filter('h1')->text());
    //      $this->assertContains('no records found', $client->getResponse()->getContent());
    // }
}
