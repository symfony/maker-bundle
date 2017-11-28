//PHP_OPEN

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class <?php echo $test_class_name; ?> extends WebTestCase
{
    public function testSomething()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame(0, $crawler->filter('html:contains("Hello World")')->count());
    }
}
