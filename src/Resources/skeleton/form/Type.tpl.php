<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>

class <?= $class_name ?> extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
<?php foreach ($form_fields as $form_field => $typeOptions): ?>
<?php if (null === $typeOptions['type'] && !$typeOptions['options_code']): ?>
            ->add('<?= $form_field ?>')
<?php elseif (null !== $typeOptions['type'] && !$typeOptions['options_code']): ?>
            ->add('<?= $form_field ?>', <?= $typeOptions['type'] ?>::class)
<?php else: ?>
            ->add('<?= $form_field ?>', <?= $typeOptions['type'] ? ($typeOptions['type'].'::class') : 'null' ?>, [
<?= $typeOptions['options_code']."\n" ?>
            ])
<?php endif; ?>
<?php endforeach; ?>
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
<?php if ($bounded_class_name): ?>
            'data_class' => <?= $bounded_class_name ?>::class,
<?php else: ?>
            // Configure your form options here
<?php endif ?>
        ]);
    }
}
