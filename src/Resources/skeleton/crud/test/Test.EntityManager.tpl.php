<?= "<?php\n" ?>
<?php use Symfony\Bundle\MakerBundle\Str; ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>

class <?= $class_name ?> extends WebTestCase<?= "\n" ?>
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '<?= $route_path; ?>/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(<?= $entity_class_name; ?>::class);

        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('<?= ucfirst($entity_var_singular); ?> index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
<?php foreach ($form_fields as $form_field => $typeOptions): ?>
            '<?= $form_field_prefix; ?>[<?= $form_field; ?>]' => 'Testing',
<?php endforeach; ?>
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->repository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new <?= $entity_class_name; ?>();
<?php foreach ($form_fields as $form_field => $typeOptions): ?>
        $fixture->set<?= ucfirst($form_field); ?>('My Title');
<?php endforeach; ?>

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('<?= ucfirst($entity_var_singular); ?>');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new <?= $entity_class_name; ?>();
<?php foreach ($form_fields as $form_field => $typeOptions): ?>
        $fixture->set<?= ucfirst($form_field); ?>('Value');
<?php endforeach; ?>

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
<?php foreach ($form_fields as $form_field => $typeOptions): ?>
            '<?= $form_field_prefix; ?>[<?= $form_field; ?>]' => 'Something New',
<?php endforeach; ?>
        ]);

        self::assertResponseRedirects('<?= $route_path; ?>/');

        $fixture = $this->repository->findAll();

<?php foreach ($form_fields as $form_field => $typeOptions): ?>
        self::assertSame('Something New', $fixture[0]->get<?= ucfirst($form_field); ?>());
<?php endforeach; ?>
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new <?= $entity_class_name; ?>();
<?php foreach ($form_fields as $form_field => $typeOptions): ?>
        $fixture->set<?= ucfirst($form_field); ?>('Value');
<?php endforeach; ?>

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('<?= $route_path; ?>/');
        self::assertSame(0, $this->repository->count([]));
    }
}
