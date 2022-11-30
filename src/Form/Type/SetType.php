<?php

namespace App\Form\Type;

use App\Form\DataTransformer\SetToNumberTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new SetToNumberTransformer($options['choices']));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multiple' => true,
            'expanded' => true,
            'choice_attr' => [],
            'disabled_choices' => false,
        ]);

        $resolver->setRequired([ 'choices', 'choice_label' ]);

        $resolver->setAllowedValues('multiple', [true]);
        $resolver->setAllowedValues('expanded', [true]);
        $resolver->setAllowedValues('disabled_choices', function ($value) {
            return false === $value || is_array($value);
        });

        $resolver->setAllowedTypes('choice_attr', 'array');

        $resolver->setNormalizer('choice_attr', function (Options $options, $value) {
            if (!is_array($options['disabled_choices']) || !is_array($value)) {
                return $value;
            }

            $disabled = 0;

            foreach ($options['disabled_choices'] as $constant) {
                $disabled |= pow(2, $constant - 1);
            }

            if (!$disabled) {
                return $value;
            }

            foreach ($options['choices'] as $k => $v) {
                $attrs = array_merge($value[$k] ?? [],
                    (pow(2, $v - 1) & $disabled) ? ['disabled' => 'disabled'] : []);
                if ($attrs) {
                    $value[$k] = $attrs;
                }
            }

            return $value;
        });
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
