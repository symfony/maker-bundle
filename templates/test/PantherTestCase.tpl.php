<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Component\Panther\PantherTestCase;

class <?= $class_name ?> extends PantherTestCase
{
    public function testSomething(): void
    {
        $client = static::createPantherClient();
        $crawler = $client->request('GET', '/');

<?php if ($web_assertions_are_available): ?>
        $this->assertSelectorTextContains('h1', 'Hello World');
<?php else: ?>
        $this->assertStringContainsString('Hello World', $crawler->filter('h1')->text());
<?php endif ?>
    }
}
