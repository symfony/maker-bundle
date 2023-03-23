<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements ?>

class <?= $class_name ?> extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'first_options' => [
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter a password',
                        ]),
                        new Length([
                            // min and max lengths recommended as per the OWASP Cheat Sheet and the hashing function limitations
                            // see https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#implement-proper-password-strength-controls
                            'min' => 8,
                            'minMessage' => 'Your password should be at least {{ limit }} characters',
                            'max' => 64,
                            'maxMessage' => 'Your password cannot be longer than {{ limit }} characters',
                        ]),
                        new NotCompromisedPassword(),
                    ],
                    'label' => 'New password',
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
                ],
                'invalid_message' => 'The password fields must match.',
                // Instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
