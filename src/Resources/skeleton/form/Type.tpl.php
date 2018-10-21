<?= "<?php" . PHP_EOL ?>

namespace <?= $namespace ?>;

<?= !empty($bounded_full_class_name) ? "use " . $bounded_full_class_name . ";" . PHP_EOL : null ?>
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class <?= $class_name ?> extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
<?php foreach ($form_fields as $form_field): ?>
            ->add('<?= $form_field ?>')
<?php endforeach ?>
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
<?php if (!empty($bounded_full_class_name)): ?>
            'data_class' => <?= $bounded_class_name ?>::class,
<?php else: ?>
            // Configure your form options here
<?php endif ?>
        ]);
    }
}
