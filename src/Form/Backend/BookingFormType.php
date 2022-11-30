<?php

namespace App\Form\Backend;

use App\Entity\Booking;
use App\Entity\Gite;
use App\Service\Backend\BookingManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingFormType extends AbstractType
{
    private $manager;

    public function __construct(BookingManager $manager)
    {
        $this->manager = $manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('start_date', DateType::class, [
                'label' => 'booking.start_date',
                'property_path' => 'startDate',
                'widget' => 'single_text',
                'input'  => 'datetime_immutable',
                'html5' => false,
                'format' => $options['date_format'],
            ])
            ->add('end_date', DateType::class, [
                'label' => 'booking.end_date',
                'property_path' => 'endDate',
                'widget' => 'single_text',
                'input'  => 'datetime_immutable',
                'html5' => false,
                'format' => $options['date_format'],
            ])
            ->add('gite', EntityType::class, [
                'label' => 'booking.gite',
                'class' => Gite::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('g')
                        ->orderBy('g.name', 'ASC');
                },
                'choice_label' => 'name',
                'required' => false,
            ])
            ->add('save', SubmitType::class)
        ;

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($options) {
                $form = $event->getForm();
                $booking = $event->getData();
                $message = '';

                if (!$this->manager->validateOverlappings($booking, $options['locale'], $message)) {
                    $form->addError(new FormError($message));
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
            'translation_domain' => 'admin',
            'locale' => 'fr',
            'date_format' => 'dd/MM/yyyy',
        ]);
    }
}
