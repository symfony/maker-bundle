<?php echo "<?php\n" ?>

namespace <?php echo $namespace ?>;

<?php if ($bounded_full_class_name) { ?>
use <?php echo $bounded_full_class_name ?>;
<?php } ?>
use Symfony\Component\Form\AbstractType;
<?php foreach ($field_type_use_statements as $className) { ?>
use <?php echo $className ?>;
<?php } ?>
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
<?php foreach ($constraint_use_statements as $className) { ?>
use <?php echo $className ?>;
<?php } ?>

class <?php echo $class_name ?> extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
<?php foreach ($form_fields as $form_field => $typeOptions) { ?>
<?php if (null === $typeOptions['type'] && !$typeOptions['options_code']) { ?>
            ->add('<?php echo $form_field ?>')
<?php } elseif (null !== $typeOptions['type'] && !$typeOptions['options_code']) { ?>
            ->add('<?php echo $form_field ?>', <?php echo $typeOptions['type'] ?>::class)
<?php } else { ?>
            ->add('<?php echo $form_field ?>', <?php echo $typeOptions['type'] ? ($typeOptions['type'].'::class') : 'null' ?>, [
<?php echo $typeOptions['options_code']."\n" ?>
            ])
<?php } ?>
<?php } ?>
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
<?php if ($bounded_full_class_name) { ?>
            'data_class' => <?php echo $bounded_class_name ?>::class,
<?php } else { ?>
            // Configure your form options here
<?php } ?>
        ]);
    }
}
