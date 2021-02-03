<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?php if ($panther_is_available): ?>
use Symfony\Component\Panther\PantherTestCase;
<?php else: ?>
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
<?php endif ?>

class <?= $class_name ?> extends <?= $panther_is_available ? 'PantherTestCase' : 'WebTestCase' ?><?= "\n" ?>
{
    public function testSomething()
    {
        $client = static::createClient(); // Use BrowserKit to simulate a web browser
<?php if ($panther_is_available): ?>
        //$client = static::createPantherClient(); // Or use the real Google Chrome and have your scripts executed
<?php endif ?>
        $crawler = $client->request('GET', '/');

<?php if ($web_assertions_are_available): ?>
<?php if (!$panther_is_available): ?>
        $this->assertResponseIsSuccessful();
<?php endif ?>
        $this->assertSelectorTextContains('h1', 'Hello World');
<?php else: ?>
<?php if (!$panther_is_available): ?>
        $this->assertSame(200, $client->getResponse()->getStatusCode());
<?php endif ?>
        $this->assertStringContainsString('Hello World', $crawler->filter('h1')->text());
<?php endif ?>
    }
}
