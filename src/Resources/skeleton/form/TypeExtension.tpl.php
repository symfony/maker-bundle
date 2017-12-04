<?= "<?php\n" ?>

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class <?= $type_extension_class_name ?> extends AbstractTypeExtension
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'option_name' => null,
        ]);
    }

    public function getExtendedType()
    {
        // returns the FQCN of the type being extended.
        return FormType::class;
    }
}
