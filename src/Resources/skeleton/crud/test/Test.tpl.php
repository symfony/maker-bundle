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
        $this->assertContains('<?= $bounded_class_name ?> index', $crawler->filter('h1')->text());
    }

    public function testNew()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '<?= $route_path ?>/new');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Create new <?= $bounded_class_name ?>', $crawler->filter('h1')->text());

        $form = $crawler->selectButton('Save')->form();

        // @TODO add your fields
        //$form['formname[fieldname]']->setValue('Lorem ipsum');

        $crawler = $client->submit($form);
        $response = $client->getResponse();

        // @TODO Find the Id
        // return $id;
    }

    // @TODO implement show test
    ///**
    // * @depends testNew
    // */
    //public function testShow($testableId)
    //{
    //    $client = static::createClient();
    //    $crawler = $client->request('GET', '<?= $route_path ?>/'.$testableId);
    //
    //    $this->assertSame(200, $client->getResponse()->getStatusCode());
    //    $this->assertContains('<?= $bounded_class_name ?>', $crawler->filter('h1')->text());
    //    $this->assertContains('Lorem ipsum', $crawler->filter('body')->text());
    //}

    // @TODO implement edit test
    ///**
    // * @depends testNew
    // */
    //public function testEdit($testableId)
    //{
    //	  $client = static::createClient();
    //    $crawler = $client->request('GET', '<?= $route_path ?>/'.$testableId.'/edit');
    //
    //    $this->assertSame(200, $client->getResponse()->getStatusCode());
    //    $this->assertContains('Edit <?= $bounded_class_name ?>', $crawler->filter('h1')->text());
    //}

    // @TODO implement delete test
    ///**
    // * @depends testNew
    // */
    //public function testDelete($testableId)
    //{
    //    // obtain csrf token with a call to show first.
    //
    //	  $client = static::createClient();
    //    $crawler = $client->request('POST', '<?= $route_path ?>/'.$testableId.'/delete');
    //
    //    $this->assertSame(200, $client->getResponse()->getStatusCode());
    //    $this->assertContains('Create new <?= $bounded_class_name ?>', $crawler->filter('h1')->text());
    //}
}
