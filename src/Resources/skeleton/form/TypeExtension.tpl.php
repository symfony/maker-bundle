<?= "<?php\n" ?>

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use <?= $extended_type_class ?>;

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
        return <?= $extended_type_class_name ?>::class;
    }
}
