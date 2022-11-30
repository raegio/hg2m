<?php

namespace App\Form\Backend;

use App\Entity\Booking;
use App\Form\Type\SetType;
use App\Model\Backend\BookingFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingFilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $parameters = $options['parameters'];
        $currentYear = $options['now']->format('Y');
        $years = 'ASC' === $options['years_order']
            ? range($currentYear - $parameters['years_back'], $currentYear + 1)
            : range($currentYear + 1, $currentYear - $parameters['years_back'], -1);
        
        $builder
            ->add('year', ChoiceType::class, [
                'label' => false,
                'choices' => $years,
                'choice_label' => function($choice, $key, $value) {
                    return $choice;
                },
                'choice_translation_domain' => false,
                'required' => false,
            ])
            ->add('search', TextType::class, [
                'label' => false,
            ])
            ->add('filter', ButtonType::class, [
                'label' => 'action.filter',
            ])
        ;

        // Disable statuses that are not relevant for the selected year, cases are:
        // more than 1 year back, between 0 and 1 year back, current year, and between 0 and 1 year forward

        $formModifier1 = function(FormInterface $form, ?int $year) use ($parameters, $years, $options) {
            if (false === $k = array_search($year, $years, true)) {
                $disabledChoices = false;
            }
            else {
                $cases = [
                    [ Booking::PLANNED, Booking::IN_PROGRESS ],
                    [ Booking::PLANNED ],
                    [],
                    [ Booking::COMPLETED ],
                ];

                $sign = function($n) {
                    return ($n > 0) - ($n < 0);
                };

                $l = 'ASC' === $options['years_order']
                    ? ($k < $parameters['years_back'] - 1 ? 0 : $sign($k - $parameters['years_back']) + 2)
                    : ($k > 2 ? 0 : $sign(1 - $k) + 2);

                $disabledChoices = $cases[$l];
            }


            $form->add('status', SetType::class, [
                'label' => false,
                'choices' => Booking::$STATUSES,
                'choice_label' => function($choice, $key, $value)  {
                    return 'booking.status.'.$key;
                },
                'disabled_choices' => $disabledChoices,
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier1) {
                $form = $event->getForm();
                $filter = $event->getData();

                $formModifier1($form, $filter->getYear());
            }
        );

        $builder->get('year')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier1) {
                $form = $event->getForm()->getParent();
                $year = $event->getForm()->getData();

                $formModifier1($form, $year);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BookingFilter::class,
            'translation_domain' => 'admin',
            'method' => 'POST',
            'parameters' => [],
            'source' => '',
            'now' => new \DateTimeImmutable,
            'years_order' => 'DESC',
        ]);

        $resolver->setAllowedValues('years_order', [ 'ASC', 'DESC' ]);
    }

    public function getBlockPrefix()
    {
        return 'bookings_filter';
    }
}
