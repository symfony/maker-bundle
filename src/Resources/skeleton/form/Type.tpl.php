<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

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
            ->add('field_name')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
<?php if ($entity_class_exists): ?>
            // uncomment if you want to bind to a class
            //'data_class' => <?= $entity_class_name ?>::class,
<?php endif; ?>
        ]);
    }
}
