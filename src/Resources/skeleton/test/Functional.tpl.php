<?php echo "<?php\n" ?>

namespace <?php echo $namespace; ?>;

<?php if ($panther_is_available) { ?>
use Symfony\Component\Panther\PantherTestCase;
<?php } else { ?>
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
<?php } ?>

class <?php echo $class_name ?> extends <?php echo $panther_is_available ? 'PantherTestCase' : 'WebTestCase' ?><?php echo "\n" ?>
{
    public function testSomething()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

<?php if ($web_assertions_are_available) { ?>
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Hello World');
<?php } else { ?>
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Hello World', $crawler->filter('h1')->text());
<?php } ?>
    }
}
