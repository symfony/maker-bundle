<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Renderer;

use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @internal
 */
final class FormTypeRenderer
{
    public function __construct(
        private Generator $generator,
    ) {
    }

    public function render(ClassNameDetails $formClassDetails, array $formFields, ClassNameDetails $boundClassDetails = null, array $constraintClasses = [], array $extraUseClasses = []): void
    {
        $fieldTypeUseStatements = [];
        $fields = [];
        foreach ($formFields as $name => $fieldTypeOptions) {
            $fieldTypeOptions ??= ['type' => null, 'options_code' => null];

            if (isset($fieldTypeOptions['type'])) {
                $fieldTypeUseStatements[] = $fieldTypeOptions['type'];
                $fieldTypeOptions['type'] = Str::getShortClassName($fieldTypeOptions['type']);
                if (\array_key_exists('extra_use_classes', $fieldTypeOptions) && \count($fieldTypeOptions['extra_use_classes']) > 0) {
                    $extraUseClasses = array_merge($extraUseClasses, $fieldTypeOptions['extra_use_classes'] ?? []);
                    $fieldTypeOptions['options_code'] = str_replace(
                        $fieldTypeOptions['extra_use_classes'],
                        array_map(fn ($class) => Str::getShortClassName($class), $fieldTypeOptions['extra_use_classes']),
                        $fieldTypeOptions['options_code']
                    );
                }
            }

            $fields[$name] = $fieldTypeOptions;
        }

        $useStatements = new UseStatementGenerator(array_unique(array_merge(
            $fieldTypeUseStatements,
            $extraUseClasses,
            $constraintClasses
        )));

        $useStatements->addUseStatement([
            AbstractType::class,
            FormBuilderInterface::class,
            OptionsResolver::class,
        ]);

        if ($boundClassDetails) {
            $useStatements->addUseStatement($boundClassDetails->getFullName());
        }

        $this->generator->generateClass(
            $formClassDetails->getFullName(),
            'form/Type.tpl.php',
            [
                'use_statements' => $useStatements,
                'bounded_class_name' => $boundClassDetails ? $boundClassDetails->getShortName() : null,
                'form_fields' => $fields,
            ]
        );
    }
}
