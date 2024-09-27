<?= "<?php\n" ?>

namespace <?= $class_data->getNamespace(); ?>;

<?= $class_data->getUseStatements(); ?>

<?= $class_data->getClassDeclaration(); ?>
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '<?= $route_path; ?>');

        self::assertResponseIsSuccessful();
    }
}
