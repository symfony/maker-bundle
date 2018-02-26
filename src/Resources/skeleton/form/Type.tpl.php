<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?php if ($entity_class_exists): ?>
use <?= $entity_full_class_name ?>;
<?php endif; ?>
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class <?= $class_name ?> extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
<?php foreach ($form_fields as $form_field): ?>
            ->add('<?= $form_field ?>')
<?php endforeach; ?>
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
<?php if ($entity_class_exists): ?>
            'data_class' => <?= $entity_class_name ?>::class,
<?php endif; ?>
        ]);
    }
}
