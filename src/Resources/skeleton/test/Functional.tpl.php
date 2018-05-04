<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?php
if ($namespace !== 'App\\Tests\\Controller'):
    echo "use App\\Tests\\Controller\\WebTestCase;\n\n";
endif;
?>
/**
 * @covers \<?= $controller."\n" ?>
 */
class <?= $class_name ?> extends WebTestCase
{
<?php
$first = true;

foreach ($route_methods as $test_name => $route_method):
    if (!$first):
        echo "\n";
    endif;

    $first = false;
?>
    public function test<?= $test_name ?>()
    {
        $client = static::createAnonymousClient();
        $crawler = $client->request('<?= $route_method ?>', '<?= $route_path ?>');

<?php
    if ($route_method === 'GET' && $is_http_get_200):
?>
        $this->assertSame(200, $client->getResponse()->getStatusCode());
<?php
    else:
?>
        // $this->assertSame(200, $client->getResponse()->getStatusCode());
<?php
    endif;
?>
    }
<?php
endforeach;
?>
}
