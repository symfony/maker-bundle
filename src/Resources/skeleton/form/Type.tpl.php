<?= "<?php\n"; ?>

namespace App\Form;

<?php if ($entity_class_name): ?>use App\Entity\<?= $entity_class_name; ?>;
<?php endif; ?>
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
<?php if ($entity_class_name): ?>use Symfony\Component\OptionsResolver\OptionsResolver;
<?php endif; ?>

class <?= $form_class_name; ?> extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
<?= $helper->getFormFieldsPrintCode($form_fields); ?>

        ;
    }
<?php if ($entity_class_name): ?>

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => <?= $entity_class_name; ?>::class,
        ]);
    }
<?php endif; ?>
}
