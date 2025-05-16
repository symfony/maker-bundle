<?= "<?php\n" ?>

namespace <?= $class_data->getNamespace(); ?>;

<?= $class_data->getUseStatements(); ?>

#[AsRemoteEventConsumer('<?= $webhook_name ?>')]
<?= $class_data->getClassDeclaration(); ?> implements ConsumerInterface
{
    public function __construct()
    {
    }

    public function consume(RemoteEvent $event): void
    {
        // Implement your own logic here
    }
}
