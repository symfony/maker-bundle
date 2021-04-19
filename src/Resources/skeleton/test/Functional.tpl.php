<?php /* @deprecated remove this method when removing make:unit-test and make:functional-test */ ?>
<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?php if ($panther_is_available): ?>
use Symfony\Component\Panther\PantherTestCase;
<?php else: ?>
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
<?php endif ?>

class <?= $class_name ?> extends <?= $panther_is_available ? 'PantherTestCase' : 'WebTestCase' ?><?= "\n" ?>
{
    public function testSomething(): void
    {
<?php if ($panther_is_available): ?>
        $client = static::createPantherClient();
<?php else: ?>
        $client = static::createClient();
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
