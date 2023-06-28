<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>

#[ODM\Document(repositoryClass: <?= $repository_class_name ?>::class)]
<?php if ($api_resource): ?>
#[ApiResource]
<?php endif ?>
class <?= $class_name."\n" ?>
{
    #[ODM\Id]
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }
}
